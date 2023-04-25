<?php

namespace Gulachek\Bassline;

class PreparedStatement
{
	public function __construct(
		private \SQLite3Stmt $stmt
	)
	{
	}

	public static function from(\SQLite3Stmt|false $stmt): ?PreparedStatement
	{
		if (!$stmt)
			return null;

		return new PreparedStatement($stmt);
	}

	public function bindValue(string $name, mixed $value): void
	{
		if (!$this->stmt->bindValue($name, $value))
			throw new \Exception("Failed to bind value to param '$name'");
	}

	public function bindValues(array $vals): void
	{
		foreach ($vals as $name => $val)
			$this->bindValue($name, $val);
	}

	public function reset(): void
	{
		if (!$this->stmt->reset())
			throw new \Exception("Failed to reset statement");
	}

	public function close(): void
	{
		$this->stmt->close();
	}

	public function exec(): void
	{
		if (!$this->stmt->execute())
			throw new \Exception("Failed to execute statement");
	}

	public function execWith(array $vals): void
	{
		$this->bindValues($vals);
		$this->exec();
		$this->reset();
	}
}
