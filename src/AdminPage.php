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
		return $arg->renderPage(
			title: 'Admin',
			template: __DIR__ . '/../template/admin_page.php',
			args: [
				'access_security' => $arg->userCan('edit_security', 'shell'),
				'access_themes' => $arg->userCan('edit_themes', 'shell')
			]
		);
	}
}
