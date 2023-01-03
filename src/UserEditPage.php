<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

class UserEditPage extends Page
{
	const USERNAME_PATTERN = "^[a-zA-Z0-9_]+$";

	public function __construct(
		private Config $config
	)
	{
	}

	public function title()
	{
		return 'Edit User';
	}

	public function stylesheets()
	{
		return [];
	}

	private function parsePattern(string $name, string $pattern, ?string &$err): ?string
	{
		if (!isset($_REQUEST[$name]))
			return null;

		$value = $_REQUEST[$name];
		if (!preg_match("/^$pattern$/", $value))
		{
			$err = "Invalid $name\n";
			return null;
		}

		return $value;
	}

	private function parseId(string $name, ?string &$err): ?int
	{
		$val = $this->parsePattern($name, '\d+', $err);

		if (is_null($val))
			return null;

		return intval($val);
	}

	private function parseUserName(string $name, ?string &$err): ?string
	{
		return $this->parsePattern($name, self::USERNAME_PATTERN, $err);
	}

	private function parseAction(): ?string
	{
		if (isset($_REQUEST['action']))
			return strtolower($_REQUEST['action']);

		return null;
	}

	private function parseErrMsg(): ?string
	{
		if (isset($_REQUEST['error']))
			return strtolower($_REQUEST['error']);

		return null;
	}

	private function parseUser(?string &$error): ?array
	{
		$id = $this->parseId('user_id', $error);
		if (!$id)
		{
			$error = $error ?? 'No user_id specified';
			return null;
		}

		$name = $this->parseUserName('username', $error);
		if (!$name)
		{
			$error = $error ?? 'No username specified';
			return null;
		}

		return [
			'id' => $id,
			'name' => $name
		];
	}

	public function body()
	{
		$db = SecurityDatabase::fromConfig($this->config);
		$USERNAME_PATTERN = self::USERNAME_PATTERN;

		$error = null;
		$user_id = $this->parseId('user_id', $error);
		$action = $this->parseAction();
		$ERROR = $this->parseErrMsg() ?? $error;

		if (is_null($action))
		{
			$action = is_null($user_id) ? 'select' : 'edit';
		}

		if ($action === 'select')
		{
			$USERS = $db->loadUsers();
			require __DIR__ . '/../template/user_edit_select.php';
			exit;
		}
		else if ($action === 'edit')
		{
			$USER = $db->loadUser($user_id);

			if ($USER)
			{
				require __DIR__ . '/../template/user_edit.php';
				exit;
			}

			$error = "User not found for id $user_id";
		}
		else if ($action === 'create')
		{
			$username = $this->parseUserName('username', $error);
			if (is_null($username))
			{
				$error = $error ?? 'No username specified';
			}
			else
			{
				$user = $db->createUser($username, $error);

				if ($user)
					$user_id = $user['id'];
			}
		}
		else if ($action === 'save')
		{
			$user = $this->parseUser($error);
			if ($user)
			{
				//var_dump($user);
				//exit;
				$db->saveUser($user, $error);
			}
		}

		$query = [];
		if (!is_null($user_id))
			$query['user_id'] = $user_id;
		if (!is_null($error))
			$query['error'] = $error; // TODO: make error codes so that site can't render bad words from crafted links

		$query_str = http_build_query($query);

		http_response_code(301);
		header("Location: /site/admin/users?$query_str");
		exit;
	}
}
