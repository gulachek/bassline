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

	private function parsePattern(string $name, string $pattern): ?string
	{
		if (!isset($_REQUEST[$name]))
			return null;

		$value = $_REQUEST[$name];
		if (!preg_match("/^$pattern$/", $value))
		{
			http_response_code(400);
			$esc = htmlspecialchars($name);
			echo "Invalid $esc\n";
			exit;
		}

		return $value;
	}

	private function parseId($name): ?int
	{
		$val = $this->parsePattern($name, '\d+');

		if (is_null($val))
			return null;

		return intval($val);
	}

	public function body()
	{
		$db = SecurityDatabase::fromConfig($this->config);
		$USERNAME_PATTERN = self::USERNAME_PATTERN;

		$user_id = $this->parseId('user_id');
		if (is_null($user_id))
		{
			$USERS = $db->loadUsers();
			require __DIR__ . '/../template/user_edit_select.php';
		}
		else if (!isset($_REQUEST['action']))
		{
			$USER = $db->loadUser($user_id);

			if (!$USER)
			{
				http_response_code(404);
				echo "User not found for id $user_id\n";
				exit;
			}

			require __DIR__ . '/../template/user_edit.php';
		}
		else
		{
			$action = strtolower($_REQUEST['action']);
			http_response_code(301);
			header("Location: /site/admin/users?user_id=$user_id");
			exit;
		}
	}
}
