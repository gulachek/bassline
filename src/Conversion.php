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

	public static function getElemType(\ReflectionProperty $prop): ?string
	{
		$attrs = $prop->getAttributes(ArrayProperty::class);
		if (count($attrs) < 1)
			return null;

		if (count($attrs) > 1)
		{
			$nm = $prop->getName();
			throw new \Exception("Only one ArrayProperty can be defined on property {$nm}");
		}

		$args = $attrs[0]->getArguments();
		if (count($args) !== 1)
		{
			$nm = $prop->getName();
			throw new \Exception("Exactly one type must be declared in ArrayProperty {$nm}");
		}

		return $args[0];
	}
}

#[Attribute]
final class AssocProperty
{
	public function __construct(
		public string $keyClass,
		public string $valueClass
	)
	{
	}

	public static function getKeyValType(\ReflectionProperty $prop, ?string &$key, ?string &$val): bool
	{
		$key = null;
		$val = null;

		$attrs = $prop->getAttributes();
		if (count($attrs) < 1)
			return false;

		if (count($attrs) > 1)
		{
			$nm = $prop->getName();
			throw new \Exception("Only one AssocProperty can be defined on property {$nm}");
		}

		$args = $attrs[0]->getArguments();
		if (count($args) !== 2)
		{
			$nm = $prop->getName();
			throw new \Exception("Exactly one key/value type must be declared in AssocProperty {$nm}");
		}

		$key = $args[0];
		$val = $args[1];
		return true;
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
			return self::fromAssoc($class, $obj, $err);
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
				$propVal = [];

				if ($elemTypeName = ArrayProperty::getElemType($prop))
				{
					if (!\array_is_list($a[$nm]))
					{
						$err = "assoc array being set to ArrayProperty {$class}.{$nm}";
						return null;
					}

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
				}
				else if (AssocProperty::getKeyValType($prop, $keyType, $valType))
				{
					foreach ($a[$nm] as $key => $val)
					{
						$keyVal = self::fromValue($keyType, $key, $keyErr);
						if (\is_null($keyVal))
						{
							$err = "Assoc property key {$class}.{$nm}[$key]: $keyErr";
							return null;
						}

						$parsedVal = self::fromValue($valType, $val, $valErr);
						if (\is_null($parsedVal))
						{
							$err = "Assoc property value {$class}.{$nm}[$key]: $valErr";
							return null;
						}

						$propVal[$keyVal] = $parsedVal;
					}
				}
				else
				{
					throw new \Exception("array property {$class}.{$nm} must have a type annotation for Conversion::fromAssoc");
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
