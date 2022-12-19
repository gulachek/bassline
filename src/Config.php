<?php

namespace Shell;

abstract class Config
{
	abstract public function apps(): array;

	abstract public function adminEmail(): string;
	abstract public function googleClientId(): string;
	abstract public function loginDatabase(): string;
	abstract public function siteName(): string;

	static public function load(?string $path = null)
	{
		$path = $path ?? $_SERVER['SITE_CONFIG_PATH'];
		if (!$path)
		{
			throw new Exception('SITE_PATH_CONFIG not configured on server');
		}

		$config = require_once($path);

		if (!($config instanceof Config))
		{
			throw new Exception('SITE_PATH_CONFIG does not return a Config instance');
		}

		return $config;
	}
}
