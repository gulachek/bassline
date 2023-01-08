<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

class ResponseDelegate
{
	public function __construct(
		public readonly Response $response,
		public readonly ?PathInfo $path
	)
	{
	}
}

// Respond to a request
abstract class Response
{
	// respond to an HTTP request handled at path
	// return null if the response is fully handled
	// return a ResponseDelegate (with delegateTo) if passing to another object
	abstract function respond(PathInfo $path): ?ResponseDelegate;

	public static function delegateTo(Response $resp, ?PathInfo $path = null): ResponseDelegate
	{
		return new ResponseDelegate($resp, $path);
	}
}
