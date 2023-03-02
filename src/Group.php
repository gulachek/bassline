<?php

namespace Shell;

require __DIR__ . '/../vendor/autoload.php';

class Group
{
	public int $id;
	public string $groupname;
	#[ArrayProperty('int')]
	public array $capabilities;
}
