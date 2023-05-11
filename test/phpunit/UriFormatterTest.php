<?php

use PHPUnit\Framework\TestCase;
use Gulachek\Bassline\UriFormatter;
use Gulachek\Bassline\PathInfo;

function create(string $path = '/app/path', bool $escapeHTML = false): UriFormatter
{
	$p = new PathInfo($path);
	return new UriFormatter($p->at(0), $p, $escapeHTML);
}

final class UriFormatterTest extends TestCase
{
	public function testAbsRootReturnsAppRoot(): void
	{
		$fmt = create();
		$this->assertEquals('/app/', $fmt->abs('/'));
	}

	public function testAbsSubPathReturnsSubPath(): void
	{
		$fmt = create();
		$this->assertEquals('/app/path/to/thing', $fmt->abs('/path/to/thing'));
	}

	public function testRelReturnsRelativeToPath(): void
	{
		$fmt = create();
		$this->assertEquals('/app/path/to/thing', $fmt->rel('to/thing'));
	}

	public function testCurReturnsCurrentPath(): void
	{
		$fmt = create('/app/path');
		$this->assertEquals('/app/path', $fmt->cur());
	}

	public function testCurQueryParameter(): void
	{
		$fmt = create('/app/path');
		$this->assertEquals('/app/path?x=1', $fmt->cur(['x' => 1]));
	}

	public function testCurQueryMultipleParameters(): void
	{
		$fmt = create('/app/path');
		$this->assertEquals('/app/path?x=1&y=2', $fmt->cur(['x' => 1, 'y' => 2]));
	}

	public function testCurQueryMultipleParametersUsesHTMLEscape(): void
	{
		$fmt = create('/app/path', escapeHTML: true);
		$this->assertEquals('/app/path?x=1&amp;y=2', $fmt->cur(['x' => 1, 'y' => 2]));
	}
}
