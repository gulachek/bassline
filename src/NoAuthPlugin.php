<?php

namespace Shell;

class NoAuthPlugin extends AuthPlugin
{
	public function __construct(
		private SecurityDatabase $db
	)
	{
	}

	public function title(): string
	{
		return 'No Auth';
	}

	public function enabled(): bool
	{
		return $this->db->authPluginEnabled('noauth');
	}

	protected function renderLoginForm(string $post_uri): void
	{
		$POST_URI = $post_uri;
		$USERS = $this->db->loadUsers();

		require(__DIR__ . '/../template/noauth_login_form.php');
	}

	function authenticate(): ?int
	{
		if (!isset($_REQUEST['user-id']))
			return null;

		$user_id = intval($_REQUEST['user-id']);
		$user = $this->db->loadUser($user_id);

		if (!$user)
			return null;

		return $user_id;
	}

	protected function saveConfigEditData(
		array $data,
		SecurityDatabase $db,
		?string &$error
	): bool
	{
		// TODO: provide a way for ShellApp to give 'noauth' key to
		// plugin so that it's defined in one place
		$enabled = boolval($data['enabled']);
		$db->setAuthPluginEnabled('noauth', $enabled);
		return true;
	}

	protected function configEditData(
		SecurityDatabase $db
	): ?array
	{
		return [
			'script' => '/assets/noauthConfigEdit.js',
			'data' => [
				'enabled' => $this->enabled()
			]
		];
	}
}
