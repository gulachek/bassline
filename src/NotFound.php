<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

class NotFound extends Response
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
