<?php

namespace Hello;

require __DIR__ . '/../../vendor/autoload.php';

class HelloPage extends \Shell\Page
{
	private $msg;

	public function __construct($msg)
	{
		$this->msg = $msg;
	}

	public function title()
	{
		return "Hello {$this->msg}";
	}

	public function body()
	{
		return "<p> Hello {$this->msg} </p>";
	}
}

class LandingPage extends \Shell\Page
{
	public function title()
	{
		return "Hello";
	}

	public function body()
	{
		return '<a href="./buddy"> Hello buddy  </a>';
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

	public function route($path): ?\Shell\Page
	{
		if ($path->isRoot())
			return new LandingPage();

		if ($path->count() > 1)
			return null; // not found

		return new HelloPage($path->at(0));
	}
}
