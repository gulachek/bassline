<?php

namespace Gulachek\Bassline;

function origin(string $uri): ?string
{
	$parsed = parse_url($uri);
	if (!$parsed)
		return null;

	$origin = '';

	if (array_key_exists('scheme', $parsed))
	{
		$origin .= "{$parsed['scheme']}://";
	}

	if (array_key_exists('host', $parsed))
	{
		$origin .= $parsed['host'];
	}

	if (array_key_exists('port', $parsed))
	{
		$origin .= ":{$parsed['port']}";
	}

	return $origin;
}

class LoginPage extends Responder
{
	public function __construct(
		private Config $config,
		private array $auth_plugins
	)
	{
	}
	
	public function respond(RespondArg $arg): mixed
	{
		$referrer = '/';
		$arg->renderPage([
			'title' => 'Log in',
			'template' => __DIR__ . '/../template/login_page.php',
			'args' => [
				'plugins' => $this->auth_plugins
			]
		]);
		return null;
	}
}
