<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

#[Attribute]
final class ArrayProperty
{
	public function __construct(
		public string $elemClass
	)
	{
	}
}

class Conversion
{
	private static function propTypeNotSupported(string $class, \ReflectionProperty $prop): never
	{
		$nm = $prop->getName();
		throw new \Exception("Conversion::fromAssoc({$class}, ...): Property '{$nm}' type not supported");
	}

	private static function fromValue(string $class, mixed $obj): mixed
	{
		if ($class === 'mixed')
			return $obj;

		if ($class === 'string')
		{
			return \is_string($obj) ? $obj : null;
		}
		if ($class === 'int')
		{
			return \is_int($obj) ? $obj : null;
		}
		if ($class === 'bool')
		{
			return \is_bool($obj) ? $obj : null;
		}

		if (!\is_array($obj))
			return null;

		if (count($obj) < 1 || !\array_is_list($obj))
		{
			return self::fromAssoc($class, $obj);
		}

		return null;
	}

	public static function fromAssoc(string $class, array $a): ?object
	{
		$ref = new \ReflectionClass($class);
		$props = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);

		if ($ref->isEnum())
		{
			throw new \Exception("Conversion::fromAssoc({$class}, ...): Enum type cannot be built from assoc array");
		}

		$obj = $ref->newInstance();
		foreach ($props as $prop)
		{
			$nm = $prop->getName();

			if (!array_key_exists($nm, $a))
				continue;

			$t = $prop->getType();

			if (!($t && $t instanceof \ReflectionNamedType))
			{
				self::propTypeNotSupported($class, $prop);
			}

			if (\is_null($a[$nm]))
			{
				if ($t->allowsNull())
				{
					$prop->setValue($obj, null);
					continue;
				}
				else
				{
					return null; // bad input data
				}
			}

			$typeName = $t->getName();
			if ($typeName === 'array')
			{
				if (!\array_is_list($a[$nm]))
					return null;

				$aprops = $prop->getAttributes(ArrayProperty::class);
				if (count($aprops) !== 1)
				{
					throw new \Exception("Only one ArrayProperty can be defined on property {$nm}");
				}

				$args = $aprops[0]->getArguments();
				if (count($args) !== 1)
				{
					throw new \Exception("Only one type can be declared in ArrayProperty {$nm}");
				}

				$elemTypeName = $args[0];
				$propVal = [];
				foreach ($a[$nm] as $elem)
				{
					$elemVal = self::fromValue($elemTypeName, $elem);
					if (\is_null($elemVal))
						return null;

					array_push($propVal, $elemVal);
				}

				$prop->setValue($obj, $propVal);
			}
			else
			{
				$isnull = is_null($a);
				$propVal = self::fromValue($typeName, $a[$nm]);
				if (\is_null($propVal))
					return null;

				$prop->setValue($obj, $propVal);
			}
		}

		return $obj;
	}
}
