<?php

namespace Gulachek\Bassline;

class NotFound extends Responder
{
	public function __construct()
	{
	}

	public function respond(RespondArg $arg): mixed
	{
		return new ErrorPage(404, "Not Found", "The requested resource was not found.");
	}
}
