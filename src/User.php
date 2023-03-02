<?php

namespace Shell;

require __DIR__ . '/../vendor/autoload.php';

class User
{
	public int $id;
	public string $username;

	#[ArrayProperty('int')]
	public array $groups;

	public int $primary_group;
	public bool $is_superuser;
}
