<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

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

	protected function renderLoginForm(string $post_uri): void
	{
		$POST_URI = $post_uri;

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
}
