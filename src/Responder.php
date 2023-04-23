<?php

namespace Gulachek\Bassline;

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
	private UriFormatter $uri;

	public function __construct(
		private readonly string $app_key,
		public readonly PathInfo $path,
		private readonly ?array $user,
		private readonly Config $config,
		private readonly PathInfo $request_path
	)
	{
		$this->uri = new UriFormatter($app_key, $request_path);
	}

	public function isLoggedIn(): bool
	{
		return !\is_null($this->user);
	}

	public function uid(): ?int
	{
		return \is_null($this->user) ? null : $this->user['id'];
	}

	public function username(?int $uid = null): ?string
	{
		if (\is_null($uid) || $uid === $this->user['id'])
			return $this->user['username'];

		$db = SecurityDatabase::fromConfig($this->config);
		$user = $db->loadUser($uid);
		if (!$user)
			return null;

		return $user['username'];
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
		$db = SecurityDatabase::fromConfig($this->config);

		// should cache these
		$caps = $db->loadUserAppCapabilities($this->user['id'], $app);

		return in_array($cap, $caps);
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

		$URI = $this->uri;

		$RENDER_BODY = function() use ($args, $template, $URI) {
			$TEMPLATE = $args;
			require($template);
		};

		$SITE_NAME = $this->config->siteName();
		$APPS = $this->config->apps();
		$SHOW_ADMIN_LINK =
			$this->userCan('edit_security', 'shell')
			|| $this->userCan('edit_themes', 'shell');

		include __DIR__ . '/../template/page.php';
	}

	private function child(): RespondArg
	{
		return new RespondArg(
			$this->app_key,
			$this->path->child(),
			$this->user,
			$this->config,
			$this->request_path
		);
	}

	// absolute to app
	public function uriAbs(
		string $path,
		?array $query = null,
		?string $app = null
	): string
	{
		return $this->uri->abs($path, $query, $app);
	}

	// relative to current uri
	public function uriRel(
		string $path,
		?array $query = null
	): string
	{
		return $this->uri->rel($path, $query);
	}

	// current uri
	public function uriCur(
		?array $query = null
	): string
	{
		return $this->uri->cur($query);
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

		$assoc = json_decode($json, associative: true);
		if (!is_array($assoc))
			return null;

		$obj = Conversion::fromAssoc($class, $assoc, $err);

		if (!$obj)
		{
			var_dump($err);
			exit;
		}

		return $obj;
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
