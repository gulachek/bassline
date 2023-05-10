<?php

namespace Gulachek\Bassline;

function is_json_obj(mixed $obj): bool
{
	return is_array($obj) && !array_is_list($obj);
}

class SaveResponse extends Responder
{
	public function __construct(
		private int $errorCode,
		private array $obj
	)
	{
	}

	public function respond(RespondArg $arg): mixed
	{
		\http_response_code($this->errorCode);
		\header('Content-Type: application/json');
		echo \json_encode($this->obj);
		return null;
	}
}

class UserSaveRequest
{
	public User $user;

	public mixed $pluginData;

	public string $key;
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

	private function save(RespondArg $arg, SecurityDatabase $db): SaveResponse
	{
		$save = $arg->parseBody(UserSaveRequest::class);

		if (!$save)
			return new SaveResponse(400, [
				'errorMsg' => 'Bad request'
			]);

		// TODO validate request more strictly

		if (!$db->lock())
			return new SaveResponse(503, [
				'errorMsg' => 'System unavailable'
			]);

		try
		{
			$current_user = $db->loadUser($save->user->id);
			if (!$current_user)
				return new SaveResponse(404, [
					'errorMsg' => "User not found"
				]);

			$token = SaveToken::tryReserveEncoded(
				$arg->uid(),
				$current_user['save_token'],
				$save->key
			);

			if (!$token)
			{
				$currentToken = SaveToken::decode($current_user['save_token']);
				$uname = $arg->username($currentToken->userId);
				return new SaveResponse(409, [
					'errorMsg' => "This user is being edited by '{$uname}'. Try again when the user is no longer being edited."
				]);
			}

			$save->user->save_token = $token->encode();
			$db->saveUser($save->user, $error);
			if ($error)
				return new SaveResponse(400, [
					'errorMsg' => $error
				]);

			foreach ($save->pluginData as $key => $data)
			{
				$p = $this->auth_plugins[$key];
				if (!$p->invokeSaveUserEditData($save->user->id, $data, $db, $error))
					return new SaveResponse(400, [
						'errorMsg' => $error
					]);
			}

			return new SaveResponse(200, [
				'newKey' => $token->key
			]);
		}
		finally
		{
			$db->unlock();
		}
	}

	public function respond(RespondArg $arg): mixed
	{
		if (!$arg->userCan('edit_security'))
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
			if (!$db->lock())
				return self::systemUnavailable();

			try
			{
				$user = $db->loadUser($user_id);
				if (!$user)
					return new ErrorPage(404, 'Not Found', "User '$user_id' doesn't exist.");

				$token = SaveToken::tryReserveEncoded($arg->uid(), $user['save_token']);
				if (!$token)
				{
					$currentToken = SaveToken::decode($user['save_token']);
					$uname = $arg->username($currentToken->userId);
					return self::userUnavailable($uname);
				}

				$user['save_token'] = $token->encode();
				$userToSave = User::fromArray($user);
				$db->saveUser($userToSave, $error);

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
					'groups' => $db->loadGroups(),
					'initialSaveKey' => $token->key
				];

				ReactPage::render($arg, [
					'title' => 'Edit User',
					'scripts' => [
						'/assets/require.js',
						'/assets/user_edit.js'
					],
					'model' => $MODEL
				]);
				return null;
			}
			finally
			{
				$db->unlock();
			}
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
			return $this->save($arg, $db);
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

	private static function systemUnavailable(): ErrorPage
	{
		\header('Retry-After: 5');
		return new ErrorPage(503, 'System Unavailable', 'The system is currently too busy to allow editing users. Try again.');
	}

	private static function userUnavailable(string $uname): ErrorPage
	{
		return new ErrorPage(
			errorCode: 409, 
			title: 'User Unavailable',
			msg: "This user is being edited by '{$uname}'. Try again when the user is no longer being edited."
		);
	}

}
