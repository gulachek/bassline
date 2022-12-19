<?php

namespace Shell;

class Redirect
{
	public function __construct(
		public readonly string $location = '/',
		public readonly int $statusCode = 301
	)
	{
	}
}
