<?php

namespace Gulachek\Bassline;

class LoginPage extends Responder
{
	public function __construct(
		private Config $config,
		private array $auth_plugins
	)
	{
	}
	
	public function respond(RespondArg $arg): mixed
	{
		$referrer = '/';
		return $arg->renderPage(
			title: 'Log in',
			template: __DIR__ . '/../template/login_page.php',
			args: [
				'plugins' => $this->auth_plugins
			]
		);
	}
}
