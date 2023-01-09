<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

class ReactPage
{
	public static function render(RespondArg $arg, array $args): void
	{
		$renderPageArgs = [
			'title' => $args['title'],
			'template' => __DIR__ . '/../template/react_page.php',
			'args' => [
				'model' => $args['model'] ?? [],
				'script' => $args['script']
			]
		];

		$arg->renderPage($renderPageArgs);
	}
}
