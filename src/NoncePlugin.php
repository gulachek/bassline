<?php

namespace Gulachek\Bassline;

class NoncePlugin extends AuthPlugin
{
	public function __construct(
		private SecurityDatabase $db
	) {
	}

	public function title(): string
	{
		return 'Nonce';
	}

	public function enabled(): bool
	{
		return true;
	}

	public function isVisible(SecurityDatabase $db)
	{
		return $db->authPluginVisible('nonce');
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

	protected function saveConfigEditData(
		array $data,
		SecurityDatabase $db,
		?string &$error
	): bool {
		$visible = \boolval($data['visible']);
		$db->setAuthPluginVisible('nonce', $visible);
		return true;
	}

	protected function configEditData(
		SecurityDatabase $db
	): ?array {
		return [
			'script' => '/assets/nonceConfigEdit.js',
			'data' => [
				'visible' => $this->isVisible($db)
			]
		];
	}
}
