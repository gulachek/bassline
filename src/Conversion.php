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

	private static function fromValue(string $class, mixed $obj, ?string &$err): mixed
	{
		$err = null;

		if ($class === 'mixed')
			return $obj;

		if ($class === 'string')
		{
			if (\is_string($obj))
				return $obj;

			$err = 'not string';
			return null;
		}
		if ($class === 'int')
		{
			if (\is_int($obj))
				return $obj;

			$err = 'not int';
			return null;
		}
		if ($class === 'bool')
		{
			if (\is_bool($obj))
				return $obj;

			if ($obj === 1)
				return true;

			if ($obj === 0)
				return true;

			$err = 'not bool';
			return null;
		}

		if (!\is_array($obj))
		{
			$err = 'not array';
			return null;
		}

		if (count($obj) < 1 || !\array_is_list($obj))
		{
			return self::fromAssoc($class, $obj);
		}

		$err = 'array value cannot be list';
		return null;
	}

	public static function fromAssoc(string $class, array $a, ?string &$err = null): ?object
	{
		$err = null;

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
			{
				if ($prop->isInitialized($obj))
				{
					continue;
				}
				else
				{
					$err = "{$class}.{$nm} is required but not provided";
					return null; // required if no default
				}
			}

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
					$err = "null being set on non-nullable {$class}.{$nm}";
					return null;
				}
			}

			$typeName = $t->getName();
			if ($typeName === 'array')
			{
				if (!\array_is_list($a[$nm]))
				{
					$err = "assoc array being set to ArrayProperty {$class}.{$nm}";
					return null;
				}

				$aprops = $prop->getAttributes(ArrayProperty::class);
				if (count($aprops) !== 1)
				{
					if (count($aprops) < 1)
						throw new \Exception("An ArrayProperty must be defined on property {$nm}");
					else
						throw new \Exception("Only one ArrayProperty can be defined on property {$nm}");
				}

				$args = $aprops[0]->getArguments();
				if (count($args) !== 1)
				{
					throw new \Exception("Only one type can be declared in ArrayProperty {$nm}");
				}

				$elemTypeName = $args[0];
				$propVal = [];
				$i = 0;
				foreach ($a[$nm] as $elem)
				{
					$elemVal = self::fromValue($elemTypeName, $elem, $elemErr);
					if (\is_null($elemVal))
					{
						$err = "Array property {$class}.{$nm}[$i]: $elemErr";
						return null;
					}

					array_push($propVal, $elemVal);
					++$i;
				}

				$prop->setValue($obj, $propVal);
			}
			else
			{
				$isnull = is_null($a);
				$propVal = self::fromValue($typeName, $a[$nm], $propErr);
				if (\is_null($propVal))
				{
					$err = "Property {$class}.{$nm}: $propErr";
					return null;
				}

				$prop->setValue($obj, $propVal);
			}
		}

		return $obj;
	}
}
