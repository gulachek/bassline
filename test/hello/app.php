<?php

namespace Hello;

require __DIR__ . '/../../vendor/autoload.php';

class HelloPage extends \Gulachek\Bassline\Responder
{
	private $msg;

	public function __construct($msg)
	{
		$this->msg = $msg;
	}

	public function respond($arg): mixed
	{
		return $arg->renderPage(
			title: "Hello {$this->msg}",
			template: __DIR__ . '/hello_page.php',
			args: [
				'msg' => $this->msg
			]
		);
	}
}

class LandingPage extends \Gulachek\Bassline\Responder
{
	public function respond($arg): mixed
	{
		return $arg->renderPage(
			title: 'Hello',
			template: __DIR__ . '/landing_page.php',
			args: [
				'steve_uri' => $arg->uriAbs('/steve')
			]
		);
	}
}

class App extends \Gulachek\Bassline\App
{
	public function __construct()
	{
		parent::__construct(__DIR__);
	}

	public function title(): string
	{
		return 'Hello';
	}

	public function version(): \Gulachek\Bassline\Semver
	{
		return new \Gulachek\Bassline\Semver(0,1,1);
	}

	public function install(): ?string
	{
		return null;
	}

	public function upgradeFromVersion(\Gulachek\Bassline\Semver $version): ?string
	{
		return null;
	}

	public function colors(): array
	{
		return [
			'greeting' => [
				'description' => 'Background color for a greeting, duh?',
				'example-uri' => '/',
				'default-system' => \Gulachek\Bassline\SystemColor::CANVAS,
			],
			'greeting-text' => [
				'description' => 'Text color for a greeting, duh?',
				'example-uri' => '/',
				'default-system' => \Gulachek\Bassline\SystemColor::CANVAS_TEXT,
			],
			'title' => [
				'description' => 'Background color for someone\'s title',
				'example-uri' => '/',
				'default-system' => \Gulachek\Bassline\SystemColor::CANVAS,
			],
			'title-text' => [
				'description' => 'Text color for someone\'s title',
				'example-uri' => '/',
				'default-system' => \Gulachek\Bassline\SystemColor::CANVAS_TEXT,
			],
		];
	}

	public function capabilities(): array
	{
		return [
			'edit_greeting' => [
				'description' => 'User can edit the main greeting'
			]
		];
	}

	public function respond($arg): mixed
	{
		$path = $arg->path;

		if ($path->isRoot())
			return new LandingPage();

		if ($path->count() > 1)
		{
			\http_response_code(404);
			echo "Not found";
			return null;
		}

		return new HelloPage($path->at(0));
	}
}
