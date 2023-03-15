<?php

namespace Gulachek\Bassline;

class SRGB
{
	public function __construct(
		private readonly int $r,
		private readonly int $g,
		private readonly int $b
	)
	{
		if ($r < 0 || $r > 255
			|| $g < 0 || $g > 255
			|| $b < 0 || $b > 255
		)
		{
			throw new \Exception('out of range');
		}
	}

	public static function fromRGB(array $rgb): SRGB
	{
		if (count($rgb) !== 3)
			throw new \Exception('invalid rgb array');

		return new SRGB($rgb[0], $rgb[1], $rgb[2]);
	}

	public function toRGB(): array
	{
		return [$this->r, $this->g, $this->b];
	}

	public static function fromHex(string $hex): SRGB
	{
		$hex = strtolower($hex);

		if (!preg_match('/^#[0-9a-f]{6}$/', $hex))
			throw new \Exception('Invalid hex string');

		$rgb = [0, 0, 0];
		for ($i = 0; $i < 3; ++$i)
		{
			$channel_hex = substr($hex, 1 + 2*$i, 2);
			$rgb[$i] = ord(hex2bin($channel_hex));
		}

		return self::fromRGB($rgb);
	}

	public function toHex(): string
	{
		$rgb = $this->toRGB();
		$hex = "#";
		foreach ($rgb as $byte)
			$hex .= bin2hex(chr($byte));

		return $hex;
	}

	// https://www.rapidtables.com/convert/color/hsl-to-rgb.html
	public static function fromHSL(array $hsl): SRGB
	{
		if (count($hsl) !== 3)
			throw new \Exception('Invalid hsl array');

		list($h, $s, $l) = $hsl;

		if ($h < 0 || $h >= 360)
			throw new \Exception("h out of range: $h");

		if ($s < 0 || $s > 1)
		{
			throw new \Exception("s out of range: $s");
		}

		if ($l < 0 || $l > 1)
			throw new \Exception("l out of range: $l");

		$c = (1 - abs(2*$l - 1)) * $s;
		$x = $c * (1 - abs(fmod(($h/60), 2) - 1));
		$m = $l - $c/2;

		$norm = null;
		if (0 <= $h && $h < 60)
			$norm = [$c,$x,0];
		else if (60 <= $h && $h < 120)
			$norm = [$x,$c,0];
		else if (120 <= $h && $h < 180)
			$norm = [0,$c,$x];
		else if (180 <= $h && $h < 240)
			$norm = [0,$x,$c];
		else if (240 <= $h && $h < 300)
			$norm = [$x,0,$c];
		else
			$norm = [$c,0,$x];

		// map [0,1] to [0,255]
		$to_byte = function ($x) {
			$b = round($x * 255);
			if ($b < 0)
				return 0;
			if ($b > 255)
				return 255;
			return $b;
		};

		return self::fromRGB(array_map(fn($x) => $to_byte($x + $m), $norm));
	}

	// https://www.rapidtables.com/convert/color/rgb-to-hsl.html
	public function toHSL(): array
	{
		$rgb = $this->toRGB();
		$norm = array_map(fn($b) => $b/255, $rgb);
		$cmax = max($norm);
		$cmin = min($norm);
		$del = $cmax - $cmin;
		$l = ($cmax + $cmin)/2;

		$h = 0;
		$s = 0;

		if ($del)
		{
			list($rp, $gp, $bp) = $norm;
			switch ($cmax)
			{
				case $rp:
					$h = 60 * fmod(($gp-$bp)/$del, 6);
					break;
				case $gp:
					$h = 60 * ((($bp-$rp)/$del) + 2);
					break;
				case $bp:
					$h = 60 * ((($rp-$gp)/$del) + 4);
					break;
				default:
					throw new \Exception('not expected hsl calculation');
					break;
			}

			$s = $del / (1 - abs(2*$l - 1));
		}

		if ($h < 0)
			$h += 360;

		// handle floating point precision issues
		if ($s < 0)
			$s = 0;

		if ($s > 1)
			$s = 1;

		if ($l < 0)
			$l = 0;

		if ($l > 1)
			$l = 1;

		return [$h, $s, $l];
	}

	public function isEqualTo(SRGB $other): bool
	{
		return $this->r === $other->r
			&& $this->g === $other->g
			&& $this->b === $other->b
			;
	}

	public function __toString(): string
	{
		return $this->toHex();
	}
}
