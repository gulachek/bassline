<?php

namespace Shell;

class Semver implements \Stringable
{
	public function __construct(
		public int $major,
		public int $minor,
		public int $patch
	)
	{
	}

	public static function parse(string $version): Semver
	{
		$pieces = explode('.', $version);
		$n = count($pieces);
		if ($n !== 3)
			throw new \Exception("version has invalid number of pieces: $n");

		$ints = array_map('intval', $pieces);
		return new Semver($ints[0], $ints[1], $ints[2]);
	}

	public function canUse(Semver $api): bool
	{
		// incompatible
		if ($this->major !== $api->major)
			return false;

		if ($this->major === 0)
		{
			// experimental API incompatible
			if ($this->minor !== $api->minor)
				return false;

			// experimental API has new feature
			return $this->patch <= $api->patch;
		}

		// API lacks required feature
		if ($this->minor > $api->minor)
			return false;

		// API has all necessary fixes
		return $this->patch <= $api->patch;
	}

	public function canSupport(Semver $consumer): bool
	{
		return $consumer->canUse($this);
	}

	public function __toString(): string
	{
		return "{$this->major}.{$this->minor}.{$this->patch}";
	}
}
