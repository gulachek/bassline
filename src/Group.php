<?php

namespace Shell;

require __DIR__ . '/../vendor/autoload.php';

class Group
{
	public function __construct(
		public int $id,
		public string $groupname,
		public array $capabilities
	)
	{
	}
}
