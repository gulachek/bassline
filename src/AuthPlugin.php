<?php

namespace Gulachek\Bassline;

function origin(?string $uri): ?string
{
	if (!$uri)
		return null;

	$parsed = parse_url($uri);
	if (!$parsed)
		return null;

	$origin = '';

	if (\array_key_exists('scheme', $parsed))
	{
		$origin .= "{$parsed['scheme']}://";
	}

	if (\array_key_exists('host', $parsed))
	{
		$origin .= $parsed['host'];
	}

	if (\array_key_exists('port', $parsed))
	{
		$origin .= ":{$parsed['port']}";
	}

	return $origin;
}

abstract class AuthPlugin
{
	abstract function title(): string;
	abstract function authenticate(): ?int;
	abstract function enabled(): bool;
	abstract protected function renderLoginForm(string $post_uri): void;

	final public function invokeRenderLoginForm(string $key): void
	{
		$referrer = '/';

		$referrer_origin = origin($_SERVER['HTTP_REFERER'] ?? null);
		$host = $_SERVER['HTTP_HOST'] ?? "{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}";
		$scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
		$host_origin = origin("$scheme://$host");

		if ($referrer_origin && $referrer_origin === $host_origin)
			$referrer = $_SERVER['HTTP_REFERER'];

		$referrer = urlencode($referrer);
		$auth = urlencode($key);

		$post_uri = "/login/attempt?auth=$auth&redirect_uri=$referrer";
		if ($host_origin)
			$post_uri = "$host_origin$post_uri";

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

	protected function configEditData(
		SecurityDatabase $db
	): ?array
	{
		return null;
	}

	protected function saveConfigEditData(
		array $data,
		SecurityDatabase $db,
		?string &$error
	): bool
	{
		$error = 'not supported';
		return false;
	}

	final public function invokeSaveConfigEditData(
		array $data,
		SecurityDatabase $db,
		?string &$error
	): bool
	{
		return $this->saveConfigEditData($data, $db, $error);
	}

	final public function getConfigEditData(
		string $key,
		SecurityDatabase $db
	): ?array
	{
		$props = $this->configEditData($db);
		if (!$props)
			return null;

		foreach (['script', 'data'] as $prop)
			if (!isset($props[$prop]))
				throw new \Exception("configEditData must have a '$prop' property");

		return [
			'title' => $this->title(),
			'script' => $props['script'],
			'data' => $props['data'],
			'key' => $key
		];
	}
}
