<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mime_type.php';

class App
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

	protected function handler(string $method): Handler
	{
		return new MethodHandler($this, $method);
	}

	// Array to objects that can be handled (Page, Redirector, etc)
	public function route(PathInfo $path): array | Page | Redirector | Handler | null
	{
		return null;
	}

	public function staticDirs(): array
	{
		return [
			'static' => "{$this->docroot}/static",
			'assets' => "{$this->docroot}/assets"
		];
	}

	public function colors(): array
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

class MethodHandler extends Handler
{
	public function __construct(
		private Mixed $obj,
		private string $method
	)
	{
	}

	public function handleRequest(): Page | Redirect | null
	{
		$obj = $this->obj;
		$method = $this->method;
		return $obj->$method();
	}
}
