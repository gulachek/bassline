<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

class NoAuthPlugin extends AuthPlugin
{
	public function __construct(
	)
	{
	}

	public function title(): string
	{
		return 'No Auth';
	}
}
