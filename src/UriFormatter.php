<?php

namespace Gulachek\Bassline;

class UriFormatter
{
	public function __construct(
		private string $app,
		private PathInfo $path
	)
	{
	}

	private static function addQuery(string $path, ?array $query): string
	{
		if (\is_null($query))
			return \htmlspecialchars($path);

		$queryStr = \http_build_query($query);
		return \htmlspecialchars("{$path}?{$queryStr}");
	}

	public function abs(string $path,
		?array $query = null,
		?string $app = null
	): string
	{
		if (!\str_starts_with($path, '/'))
		{
			throw new \Exception("UriFormatter::abs(): \$path must be absolute. '$path' does not start with a '/'");
		}

		$app = $app ?? $this->app;
		return self::addQuery("/$app$path", $query);
	}

	public function rel(string $path, ?array $query = null): string
	{
		$cat = $this->path->concat($path);
		return self::addQuery($cat->path(), $query);
	}

	public function cur(?array $query = null)
	{
		return self::addQuery($this->path->path(), $query);
	}
}
