<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

class SignInWithGoogle extends AuthPlugin
{
	public function __construct(
		private SecurityDatabase $db,
		private string $google_client_id
	)
	{
	}

	public function title(): string
	{
		return 'Sign in with Google';
	}

	public function enabled(): bool
	{
		// TODO: implement this
		return true;//$this->db->authPluginEnabled('siwg');
	}

	protected function renderLoginForm(string $post_uri): void
	{
		$GOOGLE_CLIENT_ID = $this->google_client_id;
		$SIWG_REQUEST_URI = $post_uri;

		require(__DIR__ . '/../template/siwg_login_form.php');
	}

	function authenticate(): ?int
	{
		return $this->db->signInWithGoogle($this->google_client_id);
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
