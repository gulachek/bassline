<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

class SignInWithGoogle extends AuthPlugin
{
	public function __construct(
	)
	{
	}

	public function title(): string
	{
		return 'Sign in with Google';
	}

	protected function saveUserEditData(
		int $user_id,
		array $data,
		SecurityDatabase $db,
		?string &$error
	): bool
	{
		// TODO: filter array
		return $db->saveGmail($user_id, $data, $error);
	}

	protected function userEditData(
		int $user_id,
		SecurityDatabase $db
	): ?array
	{
		// eventually might make sense to not have any siwg logic in SecurityDatabase
		$gmails = $db->loadGmail($user_id);

		return [
			'script' => '/assets/siwg_edit.js',
			'data' => $gmails
		];
	}
}
