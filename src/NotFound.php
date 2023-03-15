<?php

namespace Gulachek\Bassline;

class NotFound extends Responder
{
	public function __construct()
	{
	}

	public function respond(RespondArg $arg): mixed
	{
		http_response_code(404);
		echo "Not found\n";
		exit;
	}
}
