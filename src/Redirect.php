<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

class Redirect extends Response
{
	public function __construct(
		public readonly string $location = '/',
		public readonly int $statusCode = 301
	)
	{
	}

	public function respond(RespondArg $arg): mixed
	{
		header("Location: {$this->location}", true, $this->statusCode);
		return null;
	}
}
