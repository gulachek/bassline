<?php

namespace Gulachek\Bassline;

class SignInWithGoogle extends AuthPlugin
{
	public function __construct(
		private SecurityDatabase $db
	)
	{
	}

	public function title(): string
	{
		return 'Sign in with Google';
	}

	public function enabled(): bool
	{
		return $this->db->authPluginEnabled('siwg') &&
			strlen($this->db->googleClientId()) > 0;
	}

	protected function renderLoginForm(string $post_uri): void
	{
		$GOOGLE_CLIENT_ID = $this->db->googleClientId();
		$SIWG_REQUEST_URI = $post_uri;
		require(__DIR__ . '/../template/siwg_login_form.php');
	}

	function authenticate(): ?int
	{
		return $this->db->signInWithGoogle();
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

	protected function saveConfigEditData(
		array $data,
		SecurityDatabase $db,
		?string &$error
	): bool
	{
		$enabled = boolval($data['enabled'] ?? '');
		$clientId = $data['clientId'] ?? '';
		if (strlen($clientId) > 256)
		{
			$error = "Invalid clientId. Too long.";
			return false;
		}

		$db->setAuthPluginEnabled('siwg', $enabled);
		$db->setGoogleClientId($clientId);
		return true;
	}

	protected function configEditData(
		SecurityDatabase $db
	): ?array
	{
		return [
			'script' => '/assets/siwgConfigEdit.js',
			'data' => [
				'enabled' => $this->db->authPluginEnabled('siwg'),
				'clientId' => $db->googleClientId()
			]
		];
	}
}
