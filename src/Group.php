<?php

namespace Gulachek\Bassline;

class Group
{
	public int $id;
	public string $groupname;
	#[ArrayProperty('int')]
	public array $capabilities;
}
