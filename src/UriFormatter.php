<?php

namespace Gulachek\Bassline;

class UriFormatter
{
	public function __construct(
		private string $app,
		private PathInfo $path,
		private bool $escapeHTML = false
	)
	{
	}

	private function filter(string $str): string
	{
		if ($this->escapeHTML)
			return \htmlspecialchars($str);

		return $str;
	}

	private function addQuery(string $path, ?array $query): string
	{
		if (\is_null($query))
			return $this->filter($path);

		$queryStr = \http_build_query($query);
		return $this->filter("{$path}?{$queryStr}");
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
		return $this->addQuery("/$app$path", $query);
	}

	public function rel(string $path, ?array $query = null): string
	{
		$cat = $this->path->concat($path);
		return $this->addQuery($cat->path(), $query);
	}

	public function cur(?array $query = null)
	{
		return $this->addQuery($this->path->path(), $query);
	}
}
