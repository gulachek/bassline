<?php

use PHPUnit\Framework\TestCase;
use Gulachek\Bassline\InstallDatabase;
use Gulachek\Bassline\App;
use Gulachek\Bassline\Semver;

final class InstallDatabaseTest extends TestCase
{
	private TestApp $foo;
	private TestApp $bar;
	private array $apps;

	public function setUp(): void
	{
		$this->foo = new TestApp('1.2.3');
		$this->bar = new TestApp('2.3.4');
		$this->apps = [
			'foo' => $this->foo,
			'bar' => $this->bar
		];
	}

	public function testInstallsMultipleApps(): void
	{
		$db = InstallDatabase::createInMemory();
		$db->installToVersion($this->apps);

		$installed = $db->installedApps();
		$this->assertEquals(2, \count($installed));
		$this->assertTrue($installed['foo']->isEqualTo(new Semver(1,2,3)));
		$this->assertTrue($installed['bar']->isEqualTo(new Semver(2,3,4)));
	}
}

class TestApp extends App
{
	public function __construct(
		private readonly string $versionString
	)
	{
	}
	
	public int $installCount = 0;

	public function version(): Semver
	{
		return Semver::parse($this->versionString);
	}

	public function install(): ?string
	{
		$this->installCount += 1;
		return null;
	}
}
