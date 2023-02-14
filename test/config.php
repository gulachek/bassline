<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/hello/app.php';

class Config extends \Shell\Config
{
	private ?stdClass $json;

	public function __construct()
	{
		$this->json = json_decode(file_get_contents(__DIR__ . '/versionless-config.json'));

		$path_props = ['data-directory'];
		foreach ($path_props as $prop)
		{
			$path = $this->json->$prop;
			if (substr($path,0,1) !== '/')
			{
				$this->json->$prop = __DIR__ . '/' . $path;
			}
		}
	}

	public function apps(): array
	{
		return [
			'hello' => new \Hello\App()
		];
	}

	public function siteName(): string
	{
		return $this->json->{'site-name'};
	}

	public function dataDir(): string
	{
		$dir = getenv('DATA_DIR');
		if ($dir)
			return $dir;

		return $this->json->{'data-directory'};
	}

	public function adminEmail(): string
	{
		return $this->json->{'admin-email'};
	}

	public function googleClientId(): string
	{
		return $this->json->{'google-client-id'};
	}
}

return new Config();
