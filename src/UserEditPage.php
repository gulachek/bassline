<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

function is_json_obj(mixed $obj): bool
{
	return is_array($obj) && !array_is_list($obj);
}

class UserEditPage extends Responder
{
	const USERNAME_PATTERN = "^[a-zA-Z0-9_]+$";

	public function __construct(
		private Config $config,
		private array $auth_plugins
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

		if (!is_json_obj($obj))
		{
			$error = 'Invalid JSON object';
			return null;
		}

		return $obj;
	}

	private function parsePluginSaveData(?string &$error, array $obj): ?array
	{
		$pdata = $obj['pluginData'];

		if (!is_json_obj($pdata))
		{
			$error = 'Plugin data must be JSON object';
			return null;
		}

		foreach ($pdata as $key => $data)
		{
			if (!array_key_exists($key, $this->auth_plugins))
			{
				$error = "$key is not a valid plugin key";
				return null;
			}

			if (!is_array($data))
			{
				$error = "$key data did not deserialize as php array";
				return null;
			}
		}

		return $pdata;
	}

	private function parseSaveJson(?string &$error,
		?array &$user, ?array &$plugin_data): bool
	{
		$obj = $this->parseJsonArray($error);
		if (!$obj)
			return false;

		$user = $this->parseUser($error, $obj);
		if (!$user) return false;
		$plugin_data = $this->parsePluginSaveData($error, $obj);
		return !!$plugin_data;
	}

	private function doSave(SecurityDatabase $db): ?string
	{
		if (!$this->parseSaveJson($error, $user, $plugin_data))
			return $error;

		$db->saveUser($user, $error);
		if ($error) return $error;

		foreach ($plugin_data as $key => $data)
		{
			$p = $this->auth_plugins[$key];
			if (!$p->invokeSaveUserEditData($user['id'], $data, $db, $error))
				return $error;
		}

		return null;
	}

	public function respond(RespondArg $arg): mixed
	{
		if (!$arg->userCan('edit_users'))
		{
			http_response_code(401);
			echo "Not authorized\n";
			return null;
		}

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
					'groups' => $db->loadGroups(),
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
				$pluginData = [];
				foreach ($this->auth_plugins as $key => $plugin)
				{
					if ($data = $plugin->getUserEditData($user_id, $db))
					{
						$data['key'] = $key;
						array_push($pluginData, $data);
					}
				}

				$MODEL = [
					'errorMsg' => $ERROR,
					'user' => $user,
					'patterns' => [
						'username' => self::USERNAME_PATTERN
					],
					'authPlugins' => $pluginData,
					'groups' => $db->loadGroups()
				];

				ReactPage::render($arg, [
					'title' => 'Edit User',
					'scripts' => [
						'/static/require.js',
						'/assets/user_edit.js'
					],
					'model' => $MODEL
				]);
				return null;
			}

			$error = "User not found for id $user_id";
		}
		else if ($action === 'create')
		{
			$username = $this->parseUserName('username', $error);
			$group_id = $this->parseId('group_id', $error);
			if (is_null($username))
			{
				$error = $error ?? 'No username specified';
			}
			else
			{
				$user = $db->createUser($username, $group_id, $error);

				if ($user)
					$user_id = $user['id'];
			}
		}
		else if ($action === 'save')
		{
			$error = $this->doSave($db);

			echo json_encode([
				'errorMsg' => $error
			]);
			return null;
		}

		$query = [];
		if (!is_null($user_id))
			$query['user_id'] = $user_id;
		if (!is_null($error))
			$query['error'] = $error; // TODO: make error codes so that site can't render bad words from crafted links

		$query_str = http_build_query($query);

		http_response_code(301);
		header("Location: /site/admin/users?$query_str");
		return null;
	}
}
