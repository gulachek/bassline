<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

class AdminPage extends Responder
{
	public function __construct(
		private Config $config
	)
	{
	}
	
	public function respond(RespondArg $arg): mixed
	{
		$arg->renderPage([
			'title' => 'Admin',
			'template' => __DIR__ . '/../template/admin_page.php',
			'args' => [
				'access_users' => $arg->userCan('edit_users')
			]
		]);

		return null;
	}
}
