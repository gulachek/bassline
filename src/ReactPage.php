<?php

namespace Gulachek\Bassline;

class ReactPage
{
	public static function render(RespondArg $arg, array $args): mixed
	{
		$scripts = $args['scripts'] ?? [];
		if (isset($args['script']))
			array_push($scripts, $args['script']);

		return $arg->renderPage(
			title: $args['title'],
			template: __DIR__ . '/../template/react_page.php',
			layout: PageLayout::manual,
			args: [
				'model' => $args['model'] ?? [],
				'scripts' => $scripts
			]
		);
	}
}
