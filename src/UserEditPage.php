<?php

namespace Gulachek\Bassline;

class UserEditPage extends Responder
{
	const USERNAME_PATTERN = "^[a-zA-Z0-9_]+$";

	private SecurityDatabase $db;

	public function __construct(
		private Config $config,
		private array $auth_plugins
	)
	{
		$this->db = SecurityDatabase::fromConfig($this->config);
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

	private function save(RespondArg $arg): SaveResponse
	{
		$save = $arg->parseBody(UserSaveRequest::class);

		if (!$save)
			return new SaveResponse(400, [
				'errorMsg' => 'Bad request'
			]);

		// TODO validate request more strictly

		if (!$this->db->lock())
			return new SaveResponse(503, [
				'errorMsg' => 'System unavailable'
			]);

		try
		{
			$current_user = $this->db->loadUser($save->user->id);
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
					'errorMsg' => "This user was recently edited by '{$uname}' and the information you see may be inaccurate. You will not be able to edit this user until you successfully reload the page."
				]);
			}

			$save->user->save_token = $token->encode();
			$this->db->saveUser($save->user, $error);
			if ($error)
				return new SaveResponse(400, [
					'errorMsg' => $error
				]);

			foreach ($save->pluginData as $key => $data)
			{
				$p = $this->auth_plugins[$key];
				if (!$p->invokeSaveUserEditData($save->user->id, $data, $this->db, $error))
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
			$this->db->unlock();
		}
	}

	public function respond(RespondArg $arg): mixed
	{
		if (!$arg->userCan('edit_security'))
			return new ErrorPage(401, 'Not authorized', "You don't have permission to edit users.");

		$action = \strtolower($_REQUEST['action'] ?? 'select');

		switch ($action) {
			case 'select':
				return $this->select($arg);
			case 'edit':
				return $this->edit($arg);
			case 'create':
				return $this->create($arg);
			case 'save':
				return $this->save($arg);
			default:
				return new ErrorPage(404, 'Not Found', "Unknown action '$action'");
		}
	}

	private function select(RespondArg $arg): mixed
	{
		$arg->renderPage([
			'title' => 'Select User',
			'template' => __DIR__ . '/../template/user_edit_select.php',
			'args' => [
				'users' => $this->db->loadUsers(),
				'groups' => $this->db->loadGroups(),
				'username_pattern' => self::USERNAME_PATTERN
			]
		]);

		return null;
	}

	private function edit(RespondArg $arg): mixed
	{
		$user_id = \intval($_REQUEST['user_id'] ?? 0);

		if (!$this->db->lock())
			return self::systemUnavailable();

		try
		{
			$user = $this->db->loadUser($user_id);
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
			$this->db->saveUser($userToSave, $error);

			$pluginData = [];
			foreach ($this->auth_plugins as $key => $plugin)
			{
				if ($data = $plugin->getUserEditData($user_id, $this->db))
				{
					$data['key'] = $key;
					array_push($pluginData, $data);
				}
			}

			$model = [
				'user' => $user,
				'patterns' => [
					'username' => self::USERNAME_PATTERN
				],
				'authPlugins' => $pluginData,
				'groups' => $this->db->loadGroups(),
				'initialSaveKey' => $token->key
			];

			ReactPage::render($arg, [
				'title' => 'Edit User',
				'scripts' => [
					'/assets/require.js',
					'/assets/user_edit.js'
				],
				'model' => $model
			]);
			return null;
		}
		finally
		{
			$this->db->unlock();
		}
	}

	private function create(RespondArg $arg): mixed
	{
		$user = $this->db->createUser();
		return new Redirect($arg->uriCur(['action' => 'edit', 'user_id' => $user['id']]));
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

