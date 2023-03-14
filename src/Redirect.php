<?php

namespace Shell;

class Redirect extends Responder
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
