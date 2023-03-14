<?php

namespace Shell;

class Group
{
	public int $id;
	public string $groupname;
	#[ArrayProperty('int')]
	public array $capabilities;
}
