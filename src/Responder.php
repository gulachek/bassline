<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ResponderDelegate
{
	public function __construct(
		public readonly Responder $responder,
		public readonly ?PathInfo $path
	)
	{
	}

	public static function fromRespondReturnVal(mixed $value): ?ResponderDelegate
	{
		if (is_null($value))
			return null;

		if ($value instanceof ResponderDelegate)
			return $value;

		if ($value instanceof Responder)
			return new ResponderDelegate($value, null);

		throw new Error('Invalid return value from respond()');
	}
}

class RespondArg
{
	public function __construct(
		private readonly string $app_key,
		public readonly PathInfo $path,
		private readonly ?array $user,
		private readonly Config $config
	)
	{
	}

	public function isLoggedIn(): bool
	{
		return !is_null($this->user);
	}

	/*
	 * Can the currently logged in user has capability, return true.
	 * App defaults to currently requested app. This doesn't make
	 * much sense for an app to specify, as it shouldn't care about
	 * any other app capabilities.
	 */
	public function userCan(string $cap, ?string $app = null): bool
	{
		if (!$this->isLoggedIn())
			return false;

		if ($this->user['is_superuser'])
			return true;

		$app = $app ?? $this->app_key;

		return false;
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

	private function child(): RespondArg
	{
		return new RespondArg(
			$this->app_key,
			$this->path->child(),
			$this->user,
			$this->config
		);
	}

	public function route(array $routes): ResponderDelegate
	{
		$not_found = new ResponderDelegate(new NotFound(), null);

		if ($this->path->isRoot())
		{
			if (isset($routes['.']))
				return Responder::delegateTo($routes['.']);
			else
				return $not_found;
		}

		$item = $routes[$this->path->at(0)] ?? null;
		if (!$item)
			return $not_found;

		if (is_array($item))
			return $this->child()->route($item);

		if (!($item instanceof Responder))
			throw new \Exception('route: items must be arrays or Responder objects');

		return Responder::delegateTo($item, $this->path->child());
	}

	public function parseBody(mixed $class): mixed
	{
		$json = file_get_contents('php://input');
		if (!$json)
		{
			throw new \Exception('Failed to read request body');
		}

		$normalizer = new ObjectNormalizer();
		$serializer = new Serializer([$normalizer], [new JsonEncoder()]);

		return $serializer->deserialize($json, $class, 'json');
	}
}

// Respond to a request
abstract class Responder
{
	// respond to an HTTP request handled at path
	// return null if the response is fully handled
	// return a ResponderDelegate (with delegateTo) if passing to another object
	abstract function respond(RespondArg $args): mixed;

	public static function delegateTo(Responder $resp, ?PathInfo $path = null): ResponderDelegate
	{
		return new ResponderDelegate($resp, $path);
	}
}
