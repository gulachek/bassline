<?php

namespace Shell;

require __DIR__ . '/../vendor/autoload.php';

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

	public function initReentrant(string $email): ?string
	{
		if ($this->db->queryValue('table-exists', 'props'))
		{
			// assume this was already created and we just need to update
			return null;
		}

		$this->db->exec('init-security');

		// create admin
		$user = $this->createUser('admin', $err);
		if (!$user) return $err;

		// make super user
		$user['is_superuser'] = true;
		$this->saveUser($user, $err);
		if ($err) return $err;

		// add gmail
		$this->saveGmail($user['id'], [$email], $err);
		if ($err) return $err;

		return null;
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

	// handle a "sign in with google" request
	// https://developers.google.com/identity/gsi/web/guides/verify-google-id-token
	public function signInWithGoogle(string $google_client_id, ?string &$err = null): ?int
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
		return $this->db->queryRow('load-user', $user_id);
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

	public function createUser(string $username, ?string &$err): ?array
	{
		$current = $this->loadUserByName($username);
		if (!is_null($current))
		{
			$err = "User with username '$username' already exists.";
			return null;
		}

		$this->db->query('add-user', $username);
		return $this->db->loadRowUnsafe('users', $this->db->lastInsertRowId());
	}

	public function saveUser(array $user, ?string &$error): void
	{
		// TODO: this should be same name as column or at least consistent
		$name = $user['name'] ?? $user['username'];

		$current = $this->loadUserByName($name);
		if ($current && $current['id'] !== $user['id'])
		{
			$error = "User with username '$name' already exists.";
			return;
		}

		$this->db->query('save-user', [
			':id' => $user['id'],
			':username' => $name,
			':is_superuser' => !!($user['is_superuser'] ?? false)
		]);
	}
}
