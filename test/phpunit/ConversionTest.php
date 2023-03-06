<?php

use PHPUnit\Framework\TestCase;
use Shell\Conversion;
use Shell\ArrayProperty;
use Shell\AssocProperty;

class NoProps
{
}

class ScalarProps
{
	// Don't support constructors that can't run w/o args
	// That seems like unnecessary logic for performance parsing type
	public function __construct(
		public string $str = 'default',
		public ?string $nullableStr = 'default',
		public int $int = 0,
		public bool $bool = false,
		public mixed $null = 'not null'
	)
	{
	}
}

class ObjProp
{
	public function __construct(
		public ScalarProps $prop = new ScalarProps()
	)
	{}
}

class ArrayProp
{
	#[ArrayProperty('string')]
	public array $strs = [];

	#[ArrayProperty(ScalarProps::class)]
	public array $objs = [];

	#[AssocProperty('string', 'string')]
	public array $assoc = [];
}

class NoCtor
{
	public string $required;
	public string $optional = 'default';
}

final class ConversionTest extends TestCase
{
	public function testCreatesInstanceOfGivenType(): void
	{
		$easy = Conversion::fromAssoc(NoProps::class, []);
		$this->assertInstanceOf(NoProps::class, $easy);
	}

	public function testReadsScalarProps(): void
	{
		$obj = Conversion::fromAssoc(ScalarProps::class, [
			'str' => 'hello',
			'int' => 3,
			'bool' => true,
			'null' => null
		]);

		$this->assertEquals('hello', $obj->str);
		$this->assertEquals(3, $obj->int);
		$this->assertEquals(true, $obj->bool);
		$this->assertEquals(null, $obj->null);
	}

	public function testReadsObjProps(): void
	{
		$obj = Conversion::fromAssoc(ObjProp::class, [
			'prop' => [
				'str' => 'hello',
				'int' => 3,
		]]);

		$this->assertEquals('hello', $obj->prop->str);
		$this->assertEquals(3, $obj->prop->int);
	}

	public function testMissingPropSetsDefault(): void
	{
		$obj = Conversion::fromAssoc(ScalarProps::class, []);
		$this->assertEquals('default', $obj->str);
	}

	public function testNullCanBeSetToNullableProp(): void
	{
		$obj = Conversion::fromAssoc(ScalarProps::class, [
			'nullableStr' => null
		]);
		$this->assertNull($obj->nullableStr);
	}

	public function testNullCannotBeSetToNonNullableProp(): void
	{
		$obj = Conversion::fromAssoc(ScalarProps::class, [
			'str' => null
		]);

		$this->assertNull($obj);
	}

	public function testNullCannotBeSetToNonNullableNestedProp(): void
	{
		$obj = Conversion::fromAssoc(ObjProp::class, [ 'prop' => [
			'str' => null
		]]);

		$this->assertNull($obj);
	}

	public function testArrayProp(): void
	{
		$obj = Conversion::fromAssoc(ArrayProp::class, [
			'strs' => ['hello', 'world']
		]);

		$this->assertSame(['hello', 'world'], $obj->strs);
	}

	public function testWrongTypeArrayPropIsNull(): void
	{
		$obj = Conversion::fromAssoc(ArrayProp::class, [
			'strs' => ['hello', 3]
		]);

		$this->assertNull($obj);
	}

	public function testAssocArrayForArrayPropIsNull(): void
	{
		$obj = Conversion::fromAssoc(ArrayProp::class, [
			'strs' => ['key' => 'value']
		]);

		$this->assertNull($obj);
	}

	public function testAssocProp(): void
	{
		$obj = Conversion::fromAssoc(ArrayProp::class, [
			'assoc' => ['hello' => 'world']
		]);

		$this->assertSame(['hello' => 'world'], $obj->assoc);
	}

	public function testWrongKeyTypeAssocPropIsNull(): void
	{
		$obj = Conversion::fromAssoc(ArrayProp::class, [
			'assoc' => [1 => 'int']
		]);

		$this->assertNull($obj);
	}

	public function testWrongValTypeAssocPropIsNull(): void
	{
		$obj = Conversion::fromAssoc(ArrayProp::class, [
			'assoc' => ['int' => 3]
		]);

		$this->assertNull($obj);
	}

	public function testNestedObjectArrayProp(): void
	{
		$obj = Conversion::fromAssoc(ArrayProp::class, [
			'objs' => [
				['str' => 'hello'],
				['str' => 'world']
			]
		]);

		$this->assertEquals(2, count($obj->objs));
		$this->assertEquals('hello', $obj->objs[0]->str);
		$this->assertEquals('world', $obj->objs[1]->str);
	}

	public function testRequiredPropertyNotSpecifiedIsNull(): void
	{
		$obj = Conversion::fromAssoc(NoCtor::class, []);
		$this->assertNull($obj);
	}

	public function testBoolCanBeOneForTrue(): void
	{
		$obj = Conversion::fromAssoc(ScalarProps::class, [
			'bool' => 1
		]);

		$this->assertTrue($obj->bool);
	}

	public function testBoolCanBeZeroForFalse(): void
	{
		$obj = Conversion::fromAssoc(ScalarProps::class, [
			'bool' => 0
		]);

		$this->assertTrue($obj->bool);
	}
}
