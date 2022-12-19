<?php

namespace Shell;

require __DIR__ . '/../vendor/autoload.php';

class Server
{
	private Config $config;
	private ?PathInfo $path;

	public function __construct(?string $config_path = null)
	{
		$this->config = Config::load($config_path);
		$this->path = PathInfo::parseRequestURI();
	}

	public function initializeSystem(): bool
	{
		$db_path = $this->config->loginDatabase();
		$db = new SecurityDatabase(new Database(new \Sqlite3($db_path)));

		if ($err = $db->initReentrant(
			$this->config->adminEmail()
		))
		{
			echo "Failed to initialize security database:\n";
			echo "\t$err\n";
			return false;
		}

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

	public function render()
	{
		if (!$this->path)
			throw new \Exception('Specify a request if serving files');

		$this->doRender($this->route());
	}

	private function doRender($obj)
	{
		if ($obj instanceof Page)
		{
			$this->renderPage($obj);
		}
		else if ($obj instanceof Redirect)
		{
			header("Location: {$obj->location}", true, $obj->statusCode);
			exit;
		}
		else if ($obj instanceof Handler)
		{
			$this->doRender($obj->handleRequest());
		}
		else
		{
			http_response_code(404);
			header('Content-Type: text/plain');
			echo 'Not found';
		}
	}

	private function route()
	{
		$app = new ShellApp($this->config);
		$path = $this->path;
		$config = $this->config;

		if (!$app->isShell($path))
		{
			$apps = $config->apps();
			$app = $apps[$path->at(0)] ?? null;

			if (!$app)
				return null;

			if ($path->count() == 1 && !$path->isDir())
				return new Redirect("{$path->path()}/");

			$path = $path->child();
		}

		if ($path->isRoot())
			return $app->landingPage();

		$routee = $app->route($path);
		if (is_array($routee))
			return $this->recursiveRoute($routee, $path);

		return $routee;
	}

	private function recursiveRoute($routes, $path)
	{
		if ($path->isRoot())
		{
			return $routes['.'] ?? null;
		}

		$item = $routes[$path->at(0)] ?? null;
		if (!$item)
			return null;

		if (is_array($item))
			return $this->recursiveRoute($item, $path->child());

		// reserve namespaces for apps to implement more
		if ($path->count() == 1)
			return $item;

		return null;
	}

	private function renderPage($page)
	{
		$USER = null;
		$USERNAME = null;

		if (isset($_COOKIE['login']))
		{
			$db = new SecurityDatabase(new Database(new \Sqlite3($this->config->loginDatabase())));
			if ($user = $db->getLoggedInUser($_COOKIE['login']))
			{
				$USER = $user['id'];
				$USERNAME = $user['username'];
			}
		}

		$SITE_NAME = $this->config->siteName();
		$SHELL = new Shell($page);
		$APPS = $this->config->apps();
		include __DIR__ . '/../template/page.php';
	}
}
