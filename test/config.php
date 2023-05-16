<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/hello/app.php';

use \Gulachek\Bassline\Config;
use \Gulachek\Bassline\RespondArg;

class TestConfig extends Config
{
	public function __construct() { }

	public function apps(): array
	{
		return [
			'hello' => new \Hello\App()
		];
	}

	public function siteName(): string
	{
		return 'My Website';
	}

	public function dataDir(): string
	{
		$dir = getenv('DATA_DIR');
		if ($dir)
			return $dir;

		return __DIR__ . '/data/playground';
	}

	public function landingPage(RespondArg $arg): mixed
	{
		return $arg->renderPage(
			title: 'Landing Page',
			template: __DIR__ . '/landing_page.php'
		);
	}
}

return new TestConfig();
