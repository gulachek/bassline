<?php

namespace Shell;

require __DIR__ . '/../vendor/autoload.php';

class Server extends Responder
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

	private function allApps(): array
	{
		$apps = ['shell' => new ShellApp($this->config)];
		foreach ($this->config->apps() as $key => $app)
			$apps[$key] = $app;

		return $apps;
	}

	public function initializeSystem(): bool
	{
		// load all currently installed apps
		$installation = InstallDatabase::fromConfig($this->config);
		if ($err = $installation->initReentrant())
		{
			echo "Failed to initialize installation database: $err\n";
			return false;
		}

		$prev_apps = $installation->installedApps();
		$current_apps = $this->allApps();
		$has_err = false;

		foreach ($current_apps as $key => $app)
		{
			$err = null;
			$version = $app->version();
			$change = false;

			if (array_key_exists($key, $prev_apps))
			{
				$prev_version = $prev_apps[$key];

				if ($prev_version->isGreaterThan($version))
				{
					$err = "Cannot downgrade app '$key' from $prev_version to $version";
				}
				else if ($prev_version->isLessThan($version))
				{
					echo "Upgrading $key from $prev_version to $version\n";
					$err = $app->upgradeFromVersion($version);
					$change = true;
				}
			}
			else
			{
				echo "Installing $key $version\n";
				$err = $app->install();
				$change = true;
			}

			if ($err)
			{
				echo "Error setting up app $key:\n";
				echo "\t$err\n";
				$has_err = true;
			}
			else if ($change)
			{
				$current_apps['shell']->installApp($key, $app);
				$installation->setVersion($key, $version);
			}
		}

		$missing = "";
		$delim = "";

		// check for installed apps that no longer exist and report
		foreach ($prev_apps as $key => $version)
		{
			if (!array_key_exists($key, $current_apps))
			{
				$missing .= "{$delim}{$key}";
				$delim = ", ";
			}

		}

		if ($missing)
		{
			echo "The following apps were previously installed but "
				. "are no longer listed in the site config. Either "
				. "delete or rename them: $missing";
			$has_err = true;
		}

		return $has_err;;
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

	public function respond(RespondArg $arg): mixed
	{
		$path = $arg->path;

		// Apps control their own routing structure, but
		// they do not control the top level path to the app.
		// This is configured for site. Apps shouldn't need to
		// think about how to set up relative uri in links from
		// base page since it depends on if dir ends with '/' or
		// not (cwd is dir with '/' but parent w/o).
		if ($path->count() === 1 && !$arg->path->isDir())
			return new Redirect("{$path->path()}/");

		$app = new ShellApp($this->config);
		if ($app->isShell($path))
			return Responder::delegateTo($app, $arg->path);

		$apps = $this->config->apps();
		$app = $apps[$path->at(0)] ?? null;

		if (!$app)
			return new NotFound();

		return Responder::delegateTo($app, $path->child());
	}

	// Entry point to respond to request
	public function render()
	{
		if (!$this->path)
			throw new \Exception('Specify a request if serving files');

		$path = $this->path;

		// TODO: if requested w/o encryption, log user out
		$user = null;
		if (isset($_COOKIE['login']))
		{
			$db = SecurityDatabase::fromConfig($this->config);
			$user = $db->getLoggedInUser($_COOKIE['login']);
		}

		$resp = $this;
		$del = new ResponderDelegate($resp, $path);
		$arg = null;

		do
		{
			$resp = $del->responder;
			$path = $del->path ?? $path;
			$arg = new RespondArg($path, $user, $this->config);
		}
		while ($del = ResponderDelegate::fromRespondReturnVal($resp->respond($arg)));
	}
}
