<?php

namespace Shell;

require __DIR__ . '/../vendor/autoload.php';

class User
{
	public function __construct(
		public int $id,
		public string $username,
		public array $groups,
		public int $primary_group,
		public bool $is_superuser
	)
	{
	}
}
