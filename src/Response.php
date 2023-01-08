<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

class ResponseDelegate
{
	public function __construct(
		public readonly Response $response,
		public readonly ?PathInfo $path
	)
	{
	}

	public static function fromResponseReturnVal(mixed $value): ?ResponseDelegate
	{
		if (is_null($value))
			return null;

		if ($value instanceof ResponseDelegate)
			return $value;

		if ($value instanceof Response)
			return new ResponseDelegate($value, null);

		throw new Error('Invalid return value from respond()');
	}
}

class RespondArg
{
	public function __construct(
		public readonly PathInfo $path,
		private readonly ?array $user,
		private readonly Config $config
	)
	{
	}

	public function renderPage(array $page_args): void
	{
		$UTIL = __DIR__ . '/template_util.php';

		// TODO: use requested app title as default title
		$TITLE = "{$page_args['title']}" ?? '[Title]';

		$template = $page_args['template'];
		if (!is_readable($template))
			throw new \Exception("renderPage: 'template' is not a readable file: $template");

		$args = $page_args['args'] ?? [];
		if (!is_array($args))
			throw new \Exception("renderPage: 'args' is not an array");

		$USER = null;
		$USERNAME = null;

		if ($this->user)
		{
			$USER = $this->user['id'];
			$USERNAME = $this->user['username'];
		}

		$RENDER_BODY = function() use ($args, $template, $UTIL) {
			$TEMPLATE = $args;
			require($template);
		};

		$SITE_NAME = $this->config->siteName();
		$APPS = $this->config->apps();
		include __DIR__ . '/../template/page.php';
	}
}

// Respond to a request
abstract class Response
{
	// respond to an HTTP request handled at path
	// return null if the response is fully handled
	// return a ResponseDelegate (with delegateTo) if passing to another object
	abstract function respond(RespondArg $args): mixed;

	public static function delegateTo(Response $resp, ?PathInfo $path = null): ResponseDelegate
	{
		return new ResponseDelegate($resp, $path);
	}
}
