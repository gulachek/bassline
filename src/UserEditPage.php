<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

class UserEditPage extends Responder
{
	const USERNAME_PATTERN = "^[a-zA-Z0-9_]+$";

	public function __construct(
		private Config $config
	)
	{
	}

	private function parsePattern(string $name, string $pattern, ?string &$err, ?array $obj = null): ?string
	{
		$obj = $obj ?? $_REQUEST;

		if (!isset($obj[$name]))
			return null;

		$value = $obj[$name];
		if (!preg_match("/^$pattern$/", $value))
		{
			$err = "Invalid $name\n";
			return null;
		}

		return $value;
	}

	private function parseId(string $name, ?string &$err, ?array $obj = null): ?int
	{
		$val = $this->parsePattern($name, '\d+', $err, $obj);

		if (is_null($val))
			return null;

		return intval($val);
	}

	private function parseUserName(string $name, ?string &$err, ?array $obj = null): ?string
	{
		return $this->parsePattern($name, self::USERNAME_PATTERN, $err, $obj);
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

	private function parseUser(?string &$error, ?array $obj = null): ?array
	{
		$id = $this->parseId('user_id', $error, $obj);
		if (!is_int($id))
		{
			$error = $error ?? 'No user_id specified';
			return null;
		}

		$name = $this->parseUserName('username', $error, $obj);
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

	private function parseJsonArray(?string &$error): ?array
	{
		$post = file_get_contents('php://input');
		if (!$post)
		{
			$error = 'No post body';
			return null;
		}

		$obj = json_decode($post, true);

		if (!$obj || !is_array($obj) || array_is_list($obj))
		{
			$error = 'Invalid JSON object';
			return null;
		}

		return $obj;
	}

	private function parseUserJson(?string &$error): ?array
	{
		$obj = $this->parseJsonArray($error);
		if (!$obj)
			return null;

		return $this->parseUser($error, $obj);
	}

	public function respond(RespondArg $arg): mixed
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
			$arg->renderPage([
				'title' => 'Select User',
				'template' => __DIR__ . '/../template/user_edit_select.php',
				'args' => [
					'users' => $db->loadUsers(),
					'error' => $ERROR,
					'username_pattern' => self::USERNAME_PATTERN
				]
			]);

			return null;
		}
		else if ($action === 'edit')
		{
			$user = $db->loadUser($user_id);

			if ($user)
			{
				$MODEL = [
					'errorMsg' => $ERROR,
					'user' => $user,
					'patterns' => [
						'username' => self::USERNAME_PATTERN
					]
				];

				ReactPage::render($arg, [
					'title' => 'Edit User',
					'script' => '/assets/user_edit.js',
					'model' => $MODEL
				]);
				return null;
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
			$user = $this->parseUserJson($error);
			if ($user)
			{
				$db->saveUser($user, $error);
			}

			echo json_encode([
				'errorMsg' => $error
			]);
			exit;
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
