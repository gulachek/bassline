<?php

namespace Gulachek\Bassline;

class LoginPage extends Responder
{
	public function __construct(
		private Config $config,
		private array $auth_plugins
	) {
	}

	public function respond(RespondArg $arg): mixed
	{
		$plugins = [];
		$db = SecurityDatabase::fromConfig($this->config);
		foreach ($this->auth_plugins as $key => $plugin) {
			if ($plugin->isVisible($db))
				$plugins[$key] = $plugin;
		}

		return $arg->renderPage(
			title: 'Log in',
			template: __DIR__ . '/../template/login_page.php',
			args: [
				'plugins' => $plugins
			]
		);
	}
}
