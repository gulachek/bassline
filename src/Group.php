<?php

namespace Gulachek\Bassline;

class Group
{
	public int $id;
	public string $groupname;
	#[ArrayProperty('int')]
	public array $capabilities;

	public string $save_token;

	public static function fromArray(array $group): Group
	{
		return Conversion::fromAssoc(Group::class, $group);
	}
}
