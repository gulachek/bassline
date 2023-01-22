<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

abstract class AuthPlugin
{
	abstract function title(): string;
	abstract function authenticate(): ?int;
	abstract protected function renderLoginForm(string $post_uri): void;

	final public function invokeRenderLoginForm(string $key): void
	{
		$scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
		$self_origin = "$scheme://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}";
		if (array_key_exists('HTTP_REFERER', $_SERVER))
		{
			$referrer_origin = origin($_SERVER['HTTP_REFERER']);
			if ($self_origin && $self_origin === $referrer_origin)
			{
				$referrer = $_SERVER['HTTP_REFERER'];
			}
		}

		$referrer = urlencode($referrer);
		$auth = urlencode($key);

		$post_uri = "$self_origin/login/attempt?auth=$auth&redirect_uri=$referrer";

		$this->renderLoginForm($post_uri);
	}

	protected function userEditData(
		int $user_id,
		SecurityDatabase $db
	): ?array
	{
		return null;
	}

	protected function saveUserEditData(
		int $user_id,
		array $data,
		SecurityDatabase $db,
		?string &$error
	): bool
	{
		$error = 'not supported';
		return false;
	}

	final public function invokeSaveUserEditData(
		int $user_id,
		array $data,
		SecurityDatabase $db,
		?string &$error
	): bool
	{
		return $this->saveUserEditData($user_id, $data, $db, $error);
	}

	final public function getUserEditData(
		int $user_id,
		SecurityDatabase $db
	): ?array
	{
		$props = $this->userEditData($user_id, $db);
		if (!$props)
			return null;

		foreach (['script', 'data'] as $prop)
			if (!isset($props[$prop]))
				throw new \Exception("userEditData must have a '$prop' property");

		return [
			'title' => $this->title(),
			'script' => $props['script'],
			'data' => $props['data']
		];
	}
}
