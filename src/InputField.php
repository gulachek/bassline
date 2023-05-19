<?php

namespace Gulachek\Bassline;

class InputField
{
	public function __construct(
		public bool $required = true,
		public int $maxLength = 128,
		public ?string $title = null,
		public ?string $pattern = null
	)
	{
	}

	public function toArray(): array
	{
		$out = [];
		if ($this->required)
			$out['required'] = true;

		$out['maxLength'] = $this->maxLength;

		if ($this->title)
			$out['title'] = $this->title;

		if ($this->pattern)
			$out['pattern'] = $this->pattern;

		return $out;
	}

	public function validate(?string $value): bool
	{
		if (!$value)
			return !$this->required;

		if (\strlen($value) > $this->maxLength)
			return false;

		if ($this->pattern && !\preg_match("/{$this->pattern}/", $value))
			return false;

		return true;
	}
}
