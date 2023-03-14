<?php

namespace Shell;

class ReactPage
{
	public static function render(RespondArg $arg, array $args): void
	{
		$scripts = $args['scripts'] ?? [];
		if (isset($args['script']))
			array_push($scripts, $args['script']);

		$renderPageArgs = [
			'title' => $args['title'],
			'template' => __DIR__ . '/../template/react_page.php',
			'args' => [
				'model' => $args['model'] ?? [],
				'scripts' => $scripts
			]
		];

		$arg->renderPage($renderPageArgs);
	}
}
