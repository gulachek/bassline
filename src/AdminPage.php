<?php

namespace Gulachek\Bassline;

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
				'access_users' => $arg->userCan('edit_users'),
				'access_groups' => $arg->userCan('edit_groups'),
				'access_auth_config' => $arg->userCan('edit_auth'),
				'access_themes' => $arg->userCan('edit_themes')
			]
		]);

		return null;
	}
}
