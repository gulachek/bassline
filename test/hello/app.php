<?php

namespace Hello;

require __DIR__ . '/../../vendor/autoload.php';

class HelloPage extends \Shell\Response
{
	private $msg;

	public function __construct($msg)
	{
		$this->msg = $msg;
	}

	public function respond($arg): mixed
	{
		$arg->renderPage([
			'title' => "Hello {$this->msg}",
			'template' => __DIR__ . '/hello_page.php',
			'args' => [
				'msg' => $this->msg
			]
		]);

		return null;
	}
}

class LandingPage extends \Shell\Response
{
	public function respond($arg): mixed
	{
		$arg->renderPage([
			'title' => 'Hello',
			'template' => __DIR__ . '/landing_page.php'
		]);
		return null;
	}
}

class App extends \Shell\App
{
	public function __construct()
	{
		parent::__construct(__DIR__);
	}

	public function title(): string
	{
		return 'Hello';
	}

	public function version(): \Shell\Semver
	{
		return new \Shell\Semver(0,1,0);
	}

	public function install(): ?string
	{
		return null;
	}

	public function colors(): array
	{
		return [];
	}

	public function route($path): ?\Shell\Response
	{
		if ($path->isRoot())
			return new LandingPage();

		if ($path->count() > 1)
			return null; // not found

		return new HelloPage($path->at(0));
	}
}
