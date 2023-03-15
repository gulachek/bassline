<?php

namespace Gulachek\Bassline;

class SecurityDatabase
{
	public function __construct(
		private Database $db
	)
	{
		$this->db->mountNamedQueries(__DIR__ . '/../sql');
	}

	static function fromConfig(Config $config): SecurityDatabase
	{
		$path = "{$config->dataDir()}/security.db";
		return new SecurityDatabase(new Database( new \Sqlite3($path)));
	}

	public function initReentrant(): ?string
	{
		if ($this->db->queryValue('table-exists', 'props'))
		{
			// assume this was already created and we just need to update
			return null;
		}

		$this->db->exec('init-security');

		$group = $this->createGroup('staff', $err);
		if (!$group) return $err;

		$user = $this->createUser(
			'admin',
			$group['id'],
			$err,
			is_superuser: true,
		);

		if (!$user) return $err;

		if ($err) return $err;

		return null;
	}

	public function loadCapabilities(): array
	{
		return $this->db->query('load-capabilities')->indexById();
	}

	public function capabilityNames(string $app_key): array
	{
		$result = $this->db->query('capability-names', $app_key);
		return $result->column('name');
	}

	public function removeCapability(string $app_key, string $cap_name): void
	{
		$this->db->query('remove-capability', [
			':app' => $app_key,
			':name' => $cap_name
		]);
	}

	public function addCapability(string $app_key, string $cap_name): void
	{
		$this->db->query('add-capability', [
			':app' => $app_key,
			':name' => $cap_name
		]);
	}

	// now that caps have been added/removed, remove dangling refs, add index where needed, etc
	public function syncCapabilities(): void
	{
		$this->db->query('remove-dangling-group-capabilities');
	}

	// return the username and user ID for a logged in user
	public function getLoggedInUser(string $token): ?array
	{
		$raw_token = base64_decode($token);
		$id = $this->db->queryValue('get-login-user', $raw_token);
		if (!$id)
			return null;

		return $this->db->queryRow('load-user', $id);
	}

	public function googleClientId(): ?string
	{
		return $this->db->queryValue('get-prop', 'google-client-id');
	}

	public function setGoogleClientId(string $id): void
	{
		$this->db->query('set-prop', [
			':name' => 'google-client-id',
			':value' => $id
		]);
	}

	// handle a "sign in with google" request
	// https://developers.google.com/identity/gsi/web/guides/verify-google-id-token
	public function signInWithGoogle(?string &$err = null): ?int
	{
		if (empty($_POST["g_csrf_token"]))
		{
			$err = "POST does not contain CSRF token.";
			return null;
		}

		if (empty($_COOKIE["g_csrf_token"]))
		{
			$err = "Cookie does not contain CSRF token.\n";
			return null;
		}

		if ($_POST["g_csrf_token"] != $_COOKIE["g_csrf_token"])
		{
			$err = "Failed to validate CSRF token.";
			return null;
		}

		if (empty($_POST["credential"]))
		{
			$err = "POST does not contain credentials.";
			return null;
		}

		$google_client_id = $this->googleClientId();
		$client = new \Google_Client(['client_id' => $google_client_id]);

		$old_leeway = \Firebase\JWT\JWT::$leeway;

		try
		{
			// let's give 5min buffer for server time offset
			// see https://github.com/firebase/php-jwt/issues/475
			\Firebase\JWT\JWT::$leeway = 5*60;
			$payload = $client->verifyIdToken($_POST['credential']);
		}
		catch (\Exception $e)
		{
			$err = $e->getMessage();
			return null;
		}
		finally
		{
			\Firebase\JWT\JWT::$leeway = $old_leeway;
		}

		if (!$payload)
		{
			$err = "Invalid credentials";
			return null;
		}

		$google_id = $payload['sub'] ?? null;
		if (!$google_id)
		{
			$err = 'Google changed the way they send a user ID and this website does not yet handle the new way.';
			return null;
		}

		$email = $payload['email'] ?? null;
		if (!$email)
		{
			$err = 'The signed in user did not give this site access to his/her email, so authentication is impossible.';
			return null;
		}

		if (!($payload['email_verified'] ?? false))
		{
			$err = 'The signed in user\'s email could not be verified.';
			return null;
		}

		$google_user = $this->db->queryRow('get-google-user', $email);
		if (!$google_user)
		{
			$err = 'The user is not registered on the system.';
			return null;
		}

		if (isset($google_user['google_user_id']))
		{
			if ($google_user['google_user_id'] != $google_id)
			{
				$err = 'Something seems wrong with the signed in user\'s google account.';
				return null;
			}
		}
		else
		{
			$this->db->query('update-google-user-id', [
				':email' => $email,
				':id' => $google_id
			]);
		}

		return intval($google_user['user_id']);
	}

	public function login(int $user): ?string
	{
		// Log the user in and clean up current login session
		$token = random_bytes(256);

		$this->db->query('add-login', [
			':id' => $user,
			':token' => $token
		]);

		// after this step, the user will only have 10 most recent logins on system
		$this->db->query('limit-login', [
			':id' => $user,
			':limit' => 10
		]);

		return base64_encode($token);
	}

	public function logout(string $token): void
	{
		$this->db->query('logout', base64_decode($token));
	}

	public function loadUsers(): array
	{
		return $this->db->query('load-users')->indexById();
	}

	public function loadUser(int $user_id): ?array
	{
		$user = $this->db->queryRow('load-user', $user_id);
		$user['groups'] = $this->db->query('load-user-groups', $user_id)->column('group_id');
		return $user;
	}

	public function loadGmail(int $user_id): array
	{
		return $this->db->query('load-gmail', $user_id)->column('gmail_address');
	}

	public function saveGmail(int $user_id, array $emails, ?string &$error): bool
	{
		foreach ($emails as $email)
		{
			if (!filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				$error = "Invalid email '$email'";
				return false;
			}
		}

		$this->db->query('delete-gmail', $user_id);

		foreach ($emails as $email)
		{
			$this->db->query('add-gmail', [
				':email' => $email,
				':id' => $user_id
			]);
		}

		return true;
	}

	public function loadUserByName(string $username): ?array
	{
		return $this->db->queryRow('load-user-by-name', $username);
	}

	public function loadUserAppCapabilities(int $id, string $app): array
	{
		return $this->db->query('load-user-app-capabilities', [
			':user_id' => $id,
			':app' => $app
		])->column('name');
	}

	public function loadGroups(): array
	{
		return $this->db->query('load-groups')->indexById();
	}

	public function loadGroup(int $id): ?array
	{
		$group = $this->db->queryRow('load-group', $id);
		if (!$group)
			return null;

		$group['capabilities'] = $this->db->query('load-group-capabilities', $id)->column('cap_id');
		return $group;
	}

	public function loadGroupByName(string $groupname): ?array
	{
		return $this->db->queryRow('load-group-by-name', $groupname);
	}

	// TODO make everything use strong Group typing
	public function saveGroup(Group $group, ?string &$err): void
	{
		$err = null;
		$current = $this->loadGroup($group->id);

		if (!$current)
		{
			$error = "Group {$group->id} does not exist";
			return;
		}

		$name = $group->groupname;

		if ($current['groupname'] !== $name)
		{
			if ($this->loadGroupByName($name))
			{
				$error = "Group with groupname '$name' already exists.";
				return;
			}
		}

		$this->db->query('save-group', [
			':id' => $group->id,
			':groupname' => $name,
		]);

		$this->db->query('delete-group-capabilities', $group->id);
		foreach ($group->capabilities as $id)
		{
			$this->db->query('add-group-capability', [
				':group' => $group->id,
				':cap' => $id
			]);
		}
	}

	public function createUser(
		string $username,
		int $group_id,
		?string &$err,
		bool $is_superuser = false,
	): ?array
	{
		$current = $this->loadUserByName($username);
		if (!is_null($current))
		{
			$err = "User with username '$username' already exists.";
			return null;
		}

		$group = $this->loadGroup($group_id);
		if (!$group)
		{
			$err = "Group $group_id does not exist";
			return null;
		}

		$this->db->query('add-user', [
			':username' => $username,
			':group' => $group_id,
			':is_superuser' => $is_superuser
		]);

		$id = $this->db->lastInsertRowId();
		$this->db->query('join-group', [
			':user' => $id,
			':group' => $group_id,
		]);

		return $this->loadUser($id);
	}

	public function createGroup(string $groupname, ?string &$err): ?array
	{
		$current = $this->loadGroupByName($groupname);
		if (!is_null($current))
		{
			$err = "Group with groupname '$groupname' already exists.";
			return null;
		}

		$this->db->query('create-group', $groupname);
		return $this->db->loadRowUnsafe('groups', $this->db->lastInsertRowId());
	}

	public function saveUser(User $user, ?string &$error): void
	{
		$id = $user->id;
		$name = $user->username;

		$current = $this->loadUser($id);

		if ($current['username'] !== $name)
		{
			if ($this->loadUserByName($name))
			{
				$error = "User with username '$name' already exists.";
				return;
			}
		}

		$found_primary = false;
		foreach ($user->groups as $gid)
		{
			$group = $this->loadGroup($gid);
			if (!$group)
			{
				$error = "Invalid group id $gid";
				return;
			}

			if ($gid === $user->primary_group)
				$found_primary = true;
		}

		if (!$found_primary)
		{
			$error = "Expected user groups to contain primary group";
			return;
		}

		// do not allow changing super user from browser
		$this->db->query('save-user', [
			':id' => $id,
			':username' => $name,
			':is_superuser' => $current['is_superuser'],
			':primary_group' => $user->primary_group
		]);

		$this->db->query('forget-user-groups', $id);

		foreach ($user->groups as $gid)
		{
			$this->db->query('join-group', [
				':user' => $id,
				':group' => $gid
			]);
		}
	}

	function nonceAuthUserId(string $b64token): ?int
	{
		$token = base64_decode($b64token);
		$user_id = $this->db->queryValue('load-nonce', $token);

		if (is_int($user_id))
			$this->db->query('clear-nonce', $token);

		return $user_id;
	}

	function issueNonce(string $username, ?string &$err): ?string
	{
		$user = $this->loadUserByName($username);
		if (!$user)
		{
			$err = "User '$username' does not exist";
			return null;
		}

		$token = random_bytes(256);
		$this->db->query('issue-nonce', [
			':user_id' => $user['id'],
			':nonce' => $token
		]);

		return base64_encode($token);
	}

	function authPluginEnabled(string $key): bool
	{
		return !is_null($this->db->queryValue('get-prop', "auth-plugin-$key-enabled"));
	}

	function setAuthPluginEnabled(string $key, bool $enabled): void
	{
		$prop = "auth-plugin-$key-enabled";

		if ($enabled)
		{
			$this->db->query('set-prop', [
				':name' => $prop,
				':value' => '1'
			]);
		}
		else
		{
			$this->db->query('delete-prop', $prop);
		}
	}
}
