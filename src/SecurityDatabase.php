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
			$db_version = $this->db->queryValue('get-prop', 'version');
			$db_consumer = new Semver(0,1,0); // this is server

			if (!$db_version)
				return 'Expected a database that was set up to have a version listed in props';

			$db_semver = Semver::parse($db_version);

			// this means, "will all of the queries in my source branch work on the DB?"
			if (!$db_semver->canSupport($db_consumer))
				return "DB version ($db_semver) is incompatible with server software ($db_consumer)";

			return null; // this means the DB is set up and can support us
		}

		$this->db->exec('init-security');

		$this->db->query('add-user', [
			':id' => 0,
			':username' => 'admin'
		]);

		$this->db->query('add-gmail', [
			':id' => 0,
			':email' => $email
		]);

		return null;
	}

	// return the username and user ID for a logged in user
	public function getLoggedInUser(string $token): ?array
	{
		$raw_token = base64_decode($token);
		return $this->db->queryRow('get-login-user', $raw_token);
	}

	// handle a "sign in with google" request
	// https://developers.google.com/identity/gsi/web/guides/verify-google-id-token
	public function signInWithGoogle(string $google_client_id, ?string &$err = null): ?array
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

		return $payload;
	}

	public function loginWithGoogle(array $payload, ?string &$err): ?string
	{
		$err = null;

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

		// YAY! We now know which user we're dealing with
		$user = intval($google_user['user_id']);

		return $this->login($user);
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

		$this->db->query('create-user', $username);
		return $this->db->loadRowUnsafe('users', $this->db->lastInsertRowId());
	}
}
