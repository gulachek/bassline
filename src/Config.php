<?php

namespace Gulachek\Bassline;

abstract class Config
{
	abstract public function apps(): array;

	abstract public function dataDir(): string;
	abstract public function siteName(): string;
	abstract public function landingPage(RespondArg $arg): mixed;

	static public function load(?string $path = null)
	{
		$path = $path ?? $_SERVER['SITE_CONFIG_PATH'];
		if (!$path)
		{
			throw new \Exception('SITE_CONFIG_PATH not configured on server');
		}

		$config = require_once($path);

		if (!($config instanceof Config))
		{
			throw new \Exception('SITE_CONFIG_PATH does not return a Config instance');
		}

		return $config;
	}
}
