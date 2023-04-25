<?php

namespace Gulachek\Bassline;

class Server
{
	private Config $config;
	private ?PathInfo $path;

	public static function version(): Semver
	{
		return new Semver(0, 1, 0);
	}

	public function __construct(?string $config_path = null)
	{
		$this->config = Config::load($config_path);
		$this->path = PathInfo::parseRequestURI();
	}

	private function dataFile(string $path): string
	{
		$dir = $this->config->dataDir();
		return "$dir/$path";
	}

	private function allApps(): array
	{
		$apps = ['shell' => new ShellApp($this->config)];
		foreach ($this->config->apps() as $key => $app)
			$apps[$key] = $app;

		return $apps;
	}

	public function initializeSystem(): bool
	{
		$current_apps = $this->allApps();
		$app_table = [];
		$has_error = false;

		foreach ($this->allApps() as $key => $app)
		{
			\array_push($app_table, [
				'app' => $key,
				'version' => $app->version()
			]);
		}

		$installation = InstallDatabase::fromConfig($this->config);

		if (!$installation->lock())
		{
			echo "Failed to lock install.db";
			return false;
		}
		try
		{
			if ($err = $installation->installToVersion($current_apps))
			{
				echo "Error during app installation: $err\n";
				return false;
			}
		}
		finally
		{
			$installation->unlock();
		}

		$colors = ColorDatabase::fromConfig($this->config);
		if (!$colors->lock())
		{
			echo "Failed to lock colors.db";
			return false;
		}
		try
		{
			if ($err = $colors->installWithApps($current_apps))
			{
				echo "Error during color installation: $err\n";
				return false;
			}
		}
		finally
		{
			$colors->unlock();
		}

		$security = SecurityDatabase::fromConfig($this->config);
		if (!$security->lock())
		{
			echo "Failed to lock security.db";
			return false;
		}
		try
		{
			if ($err = $security->installWithApps($current_apps))
			{
				echo "Error during security installation: $err\n";
				return false;
			}
		}
		finally
		{
			$security->unlock();
		}

		return true;
	}

	public function issueNonce(string $username): bool
	{
		$db = SecurityDatabase::fromConfig($this->config);
		$nonce = $db->issueNonce($username, $err);
		if (!$nonce)
		{
			echo "Failed to issue nonce for user '$username'.\n"
				. "Reason: $err";
			return false;
		}

		echo "$nonce\n";
		return true;
	}

	// return true if static content was served, false otherwise
	// if the URI matches what *should* be a file but it could not be served, an error
	// response should be emitted and the function should return true
	public function serveStaticContent(): bool
	{
		if (!$this->path)
			throw new \Exception('Specify a request if serving files');

		$config = $this->config;
		$path = $this->path;

		if ($path->isRoot())
			return false;

		$shell = new ShellApp($this->config);

		$apps = $config->apps();
		$app = $apps[$path->at(0)] ?? null;
		$child_path = $path->child();

		if ($shell->isShell($path))
		{
			$app = $shell;
			$child_path = $path;
		}

		if (!$app)
			return false;

		// path on actual system instead of URI path
		$sys_path = $app->mapStaticPath($child_path);

		if (!$sys_path)
			return false;

		if (is_file($sys_path))
		{
			$types = $app->mimeTypes();
			$type = $types[$child_path->extension()];

			if ($type)
			{
				header("Content-Type: $type");
				echo file_get_contents($sys_path);
			}
			else
			{
				http_response_code(500);
				echo 'Content type not configured';
			}
		}
		else
		{
			http_response_code(404);
			header('Content-Type: text/plain');
			echo 'Not found';
		}

		return true;
	}

	// Entry point to respond to request
	public function render()
	{
		if (!$this->path)
			throw new \Exception('Specify a request if serving files');

		$path = $this->path;

		// Apps control their own routing structure, but
		// they do not control the top level path to the app.
		// This is configured for site. Apps shouldn't need to
		// think about how to set up relative uri in links from
		// base page since it depends on if dir ends with '/' or
		// not (cwd is dir with '/' but parent w/o).
		if ($path->count() === 1 && !$path->isDir())
			return new Redirect("{$path->path()}/");

		$user = null;
		if (isset($_COOKIE['login']))
		{
			$db = SecurityDatabase::fromConfig($this->config);
			$user = $db->getLoggedInUser($_COOKIE['login']);
		}

		$app = new ShellApp($this->config);
		$app_key = 'shell';
		if (!$app->isShell($path))
		{
			$apps = $this->config->apps();
			$app_key = $path->at(0);
			$app = $apps[$app_key] ?? null;
			$path = $path->child();

			if (!$app)
				return new NotFound();
		}

		$del = new ResponderDelegate($app, $path);
		$arg = null;
		$resp = null;
		$req_path = $path;

		do
		{
			$resp = $del->responder;
			$path = $del->path ?? $path;
			$arg = new RespondArg($app_key, $path, $user, $this->config, $req_path);
		}
		while ($del = ResponderDelegate::fromRespondReturnVal($resp->respond($arg)));
	}
}
