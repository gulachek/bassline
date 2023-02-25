<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/hello/app.php';

class Config extends \Shell\Config
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
}

return new Config();
