<?php

namespace Gulachek\Bassline;

class Color
{
	public function __construct(
		public readonly string $app,
		public readonly string $name,
		private array $def
	)
	{
		if (!preg_match('/^[a-zA-Z][a-zA-Z0-9-]*$/', $name))
			throw new \Exception("Invalid color name '$name'");

		$req = [
			'example-uri',
			'description',
			'default-system-fg',
			'default-system-bg'
		];

		foreach ($req as $r)
		{
			if (!is_string($this->def[$r]))
				throw new \Exception("$r not defined");
		}
	}

	public function example_path(): string
	{
		return "/{$this->app}/{$this->def['example-uri']}";
	}

	public function desc(): string
	{
		return $this->def['description'];
	}

	public function defaultFg(): string
	{
		return $this->def['default-system-fg'];
	}

	public function defaultBg(): string
	{
		return $this->def['default-system-bg'];
	}
}
