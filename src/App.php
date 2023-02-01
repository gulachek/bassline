<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mime_type.php';

class App extends Responder
{
	public function __construct(
		private string $docroot
	)
	{
	}

	public function title(): string
	{
		return '[Title]';
	}

	public function version(): Semver
	{
		throw new \Exception('not implemented');
	}

	public function install(): ?string
	{
		throw new \Exception('not implemented');
	}

	public function upgradeFromVersion(Semver $version): ?string
	{
		throw new \Exception('not implemented');
	}

	protected function handler(string $method): Responder
	{
		return new MethodHandler($this, $method);
	}

	public function respond(RespondArg $path): mixed
	{
		throw new \Exception('not implemented');
	}

	public function staticDirs(): array
	{
		return [
			'static' => "{$this->docroot}/static",
			'assets' => "{$this->docroot}/assets"
		];
	}

	/*
	 * Broadcast all semantic colors used by this app
	 */
	public function colors(): array
	{
		return [];
	}

	/*
	 * Broadcast all capabilities used by this app
	 */
	public function capabilities(): array
	{
		return [];
	}

	public function mimeTypes(): array
	{
		return default_mime_types();
	}

	// map a relative (to app) URI to a static file in staticDirs route
	public function mapStaticPath(PathInfo $info): ?string
	{
		// this should always be a page for any reasonable web application (come at me :))
		if ($info->isRoot())
			return null;

		$static = $this->staticDirs();

		$dir_urlname = $info->at(0);
		$file_info = $info->child();
		$dir = $static[$dir_urlname] ?? null;

		if (!$dir)
			return null;

		return "$dir/{$file_info->path()}";
	}
}

class MethodHandler extends Responder
{
	public function __construct(
		private mixed $obj,
		private string $method
	)
	{
	}

	public function respond(RespondArg $arg): mixed
	{
		$obj = $this->obj;
		$method = $this->method;
		return $obj->$method($arg);
	}
}
