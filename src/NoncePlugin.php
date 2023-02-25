<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

class NoncePlugin extends AuthPlugin
{
	public function __construct(
		private SecurityDatabase $db
	)
	{
	}

	public function title(): string
	{
		return 'Nonce';
	}

	protected function renderLoginForm(string $post_uri): void
	{
		$POST_URI = $post_uri;

		require(__DIR__ . '/../template/nonce_login_form.php');
	}

	function authenticate(): ?int
	{
		if (!isset($_REQUEST['nonce']))
			return null;

		return $this->db->nonceAuthUserId($_REQUEST['nonce']);
	}
}
