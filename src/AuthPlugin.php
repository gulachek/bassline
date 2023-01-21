<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

abstract class AuthPlugin
{
	abstract function title(): string;
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
