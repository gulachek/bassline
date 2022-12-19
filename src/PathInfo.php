<?php

namespace Shell;

class PathInfo
{
	private $is_dir;
	private $path;
	private $info;
	private $components;

	public function __construct($path)
	{
		$this->info = pathinfo($path);

		$this->components = [];
		foreach (explode('/', $path) as $piece)
		{
			if (!empty($piece))
				array_push($this->components, $piece);
		}

		$this->is_dir = str_ends_with($path, '/');

		$this->path = '/' . implode('/', $this->components);
	}

	public function isDir()
	{
		return $this->is_dir;
	}

	public function path()
	{
		return $this->path;
	}

	public function extension()
	{
		return $this->info['extension'];
	}

	public function dirname()
	{
		return $this->info['dirname'];
	}

	public function basename()
	{
		return $this->info['basename'];
	}

	public function filename()
	{
		return $this->info['filename'];
	}

	public function count()
	{
		return count($this->components);
	}

	public function is_root()
	{
		return !$this->count();
	}

	public function at($i)
	{
		if ($i >= 0)
		{
			if ($i >= $this->count())
				throw new \Exception("Path component index out of bounds: $i");

			return $this->components[$i];
		}
		else
			return $this->components[$this->count() + $i];
	}

	public function child()
	{
		if ($this->is_root())
		{
			return null;
		}

		$child_components = array_slice($this->components, 1);
		return new PathInfo(implode('/', $child_components));
	}

	public static function parseURI(string $uri): PathInfo
	{
		$parsed = parse_url($uri);
		return new PathInfo(strtolower($parsed['path']));
	}

	public static function parseRequestURI(): ?PathInfo
	{
		if (!isset($_SERVER['REQUEST_URI']))
			return null;

		return self::parseURI($_SERVER['REQUEST_URI']);
	}
}
