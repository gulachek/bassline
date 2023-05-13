<?php

namespace Gulachek\Bassline;

class AuthEditPage extends Responder
{
	public function __construct(
		private SecurityDatabase $db,
		private array $plugins
	)
	{
	}

	public function respond(RespondArg $arg): mixed
	{
		if (!$arg->userCan('edit_security'))
			return new ErrorPage(401, 'Not authorized', "You don't have permission to edit authentication configuration.");

		$path = $arg->path;
		if ($path->count() > 1)
			return new NotFound();

		$action = $path->isRoot() ? 'edit' : $path->at(0);

		switch ($action) {
			case 'edit':
				return $this->edit($arg);
			case 'save':
				return $this->save($arg);
			default:
				return new ErrorPage(404, 'Not Found', "Unknown action '$action'");
		}
	}

	private function save(RespondArg $arg): AuthConfigSaveResponse
	{
		if (!$this->db->lock()) {
			return new AuthConfigSaveResponse(503, [
				'errorMsg' => 'System Unavailable'
			]);
		}

		try
		{
			$save = $arg->parseBody(AuthConfigSaveRequest::class);

			$currentToken = $this->db->getAuthSaveToken();

			$token = $currentToken ?
				$currentToken->tryReserve($arg->uid(), $save->saveKey)
				: SaveToken::createForUser($arg->uid())
				;

			if (!$token)
			{
				$uname = $arg->username($currentToken->userId);
				return new AuthConfigSaveResponse(409, [
					'errorMsg' => "'{$uname}' was recently editing authentication configuration and the information you see may be inaccurate. You will not be able to edit authentication configuration until you successfully reload the page."
				]);
			}

			$this->db->setAuthSaveToken($token);

			foreach ($save->pluginData as $key => $data)
			{
				$p = $this->plugins[$key];
				if (!$p->invokeSaveConfigEditData($data, $this->db, $error))
				{
					return new AuthConfigSaveResponse(400, [
						'errorMsg' => $error
					]);
				}
			}

			return new AuthConfigSaveResponse(200, [
				'newSaveKey' => $token->key
			]);
		}
		finally
		{
			$this->db->unlock();
		}
	}

	private function edit(RespondArg $arg): mixed
	{
		if (!$this->db->lock())
			return self::systemUnavailable();

		try
		{
			$currentToken = $this->db->getAuthSaveToken();
			$token = $currentToken ?
				$currentToken->tryReserve($arg->uid())
				: SaveToken::createForUser($arg->uid())
				;

			if (!$token)
			{
				$uname = $arg->username($currentToken->userId);
				return self::authConfigUnavailable($uname);
			}

			$this->db->setAuthSaveToken($token);

			$pluginData = [];
			foreach ($this->plugins as $key => $plugin)
			{
				if ($data = $plugin->getConfigEditData($key, $this->db))
				{
					\array_push($pluginData, $data);
				}
			}

			$model = [
				'errorMsg' => null,
				'authPlugins' => $pluginData,
				'initialSaveKey' => $token->key
			];

			ReactPage::render($arg, [
				'title' => 'Authentication Configuration',
				'scripts' => [
					'/assets/require.js',
					'/assets/authConfigEdit.js'
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

	private static function systemUnavailable(): ErrorPage
	{
		\header('Retry-After: 5');
		return new ErrorPage(503, 'System Unavailable', 'The system is currently too busy to allow editing users. Try again.');
	}

	private static function authConfigUnavailable(string $uname): ErrorPage
	{
		return new ErrorPage(
			errorCode: 409, 
			title: 'Authentication Configuration Unavailable',
			msg: "This configuration is being edited by '{$uname}'. Try again when the configuration is no longer being edited."
		);
	}
}

class AuthConfigSaveResponse extends Responder
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

class AuthConfigSaveRequest
{
	public mixed $pluginData;
	public string $saveKey;
}
