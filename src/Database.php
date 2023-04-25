<?php

namespace Gulachek\Bassline;

enum TransactionType
{
	case Deferred;
	case Immediate;
	case Exclusive;
}

class Database
{
	public function __construct(
		private \SQLite3 $db,
		private ?string $query_dir = null
	)
	{
		if (PHP_VERSION_ID < 70007)
			throw new \Exception("php >= 7.0.7 needed for sqlite3 bindValue type inference");
	}

	public function mountNamedQueries(string $query_dir): void
	{
		$this->query_dir = $query_dir;
	}

	public function attach(string $name, string $path): void
	{
		if (!self::isIdentifier($name))
			throw new \Exception("Failed to attach '$name': invalid \$name parameter");

		$escPath = $this->db->escapeString($path);
		if (!$this->db->exec("ATTACH '$escPath' AS $name"))
			$this->throwSqlError("Failed to attach '$name' ('$path')");
	}

	public function lock(TransactionType $type = TransactionType::Immediate): bool
	{
		$typeStr = self::transactionType($type);
		$this->db->exec("BEGIN $typeStr TRANSACTION");
		return $this->db->lastErrorCode() === 0;
	}

	public function unlock(): void
	{
		$this->db->exec('COMMIT TRANSACTION');
	}

	public function lastInsertRowID(): int
	{
		return $this->db->lastInsertRowID();
	}

	// MAKE SURE YOU DON'T LET THE USER SPECIFY THE PARAMETERS
	public function loadRowUnsafe(string $table, int $rowid): ?array
	{
		return $this->db->querySingle("SELECT * FROM $table WHERE rowid=$rowid;", true);
	}

	private function throwSqlError(string $msg): void
	{
		$err = $this->db->lastErrorMsg();
		throw new \Exception("$msg: $err");
	}

	private function loadSql(string $sql): string
	{
		return file_get_contents("{$this->query_dir}/$sql.sql");
	}

	private static function isIdentifier(string $id): bool
	{
		return \preg_match('/^[a-zA-Z_][a-zA-Z0-9_]+$/', $id);
	}

	private static function columnType(mixed $val): string
	{
		if (\is_string($val))
			return 'TEXT';

		if (\is_int($val))
			return 'INTEGER';

		if (\is_bool($val))
			return 'INTEGER';

		if (\is_float($val))
			return 'REAL';

		throw new \Exception("Cannot infer column type from '$val'");
	}

	private static function inferColumnTypes(array $proto_row): array
	{
		$types = [];
		foreach ($proto_row as $name => $val)
			$types[$name] = self::columnType($val);

		return $types;
	}

	public function createTempTable(string $name, array $table, ?array $types = null): void
	{
		if (\count($table) < 1)
			throw new \Exception("Failed to create table '$name': expected \$table to have at least one row");

		$$types ??= self::inferColumnTypes($table[0]);

		if (!self::isIdentifier($name))
			throw new \Exception("Failed to create table '$name': invalid table name. table names must start with a letter and only use letters, underscores, or numbers");

		$col_names = [];

		$query = "CREATE TEMPORARY TABLE $name (";
		$dl = "";
		foreach ($types as $col_name => $type)
		{
			if (!self::isIdentifier($col_name))
				throw new \Exception("Failed to create table '$name': invalid column name '$col_name'. column names must start with a letter and only use letters, underscores, or numbers");

			$query .= "$dl$col_name $type";
			$dl = ", ";
		}
		$query .= ");";
		if (!$this->db->exec($query))
			$this->throwSqlError("Failed to create table '$name'");

		$insertColNames = "";
		$insertValues = "";
		$dl = "";
		foreach ($types as $col_name => $type)
		{
			$insertColNames .= "$dl$col_name";
			$insertValues .= "$dl:$col_name";
			$dl = ", ";
		}
		$insertStmt = $this->db->prepare("INSERT INTO $name ($insertColNames) VALUES ($insertValues);");
		if (!$insertStmt)
		{
			$this->throwSqlError("Failed to create table '$name': failed to prepare insert statement");
		}

		foreach ($table as $row)
		{
			foreach ($types as $col_name => $type)
			{
				if (!$insertStmt->bindValue($col_name, $row[$col_name]))
				{
					$this->throwSqlError("Failed to create table '$name': failed to bind parameter '$col_name'");
				}
			}

			if (!$insertStmt->execute())
			{
				$this->throwSqlError("Failed to create table '$name': failed to execute insert");
			}

			if (!$insertStmt->reset())
			{
				$this->throwSqlError("Failed to create table '$name': failed to reset insert statement");
			}
		}
	}

	public function dropTempTable(string $name): void
	{
		if (!self::isIdentifier($name))
			throw new \Exception("Failed to drop table '$name': invalid table name");

		if (!$this->db->exec("DROP TABLE temp.$name;"))
		{
			$this->throwSqlError("Failed to drop table '$name'");
		}
	}

	public function execRaw(string $sql): void
	{
		if (!$this->db->exec($sql))
			throw new \Exception("failed to exec query '$sql'");
	}

	public function prepareRaw(string $sql): PreparedStatement
	{
		if ($stmt = PreparedStatement::from($this->db->prepare($sql)))
			return $stmt;

		throw new \Exception("failed to prepare statement '$sql'");
	}

	public function prepare(string $sql): PreparedStatement
	{
		if ($stmt = PreparedStatement::from($this->db->prepare($this->loadSql($sql))))
			return $stmt;

		throw new \Exception("failed to prepare statement '$sql'");
	}

	public function exec(string $sql): bool
	{
		if (!$this->db->exec($this->loadSql($sql)))
		{
			throw new \Exception("failed to exec query $sql");
		}
		return true;
	}

	public function query(string $sql, mixed $params = null): QueryResult
	{
		$result = $this->tryQuery($sql, $params);
		if (!$result)
			throw new \Exception("Failed to run query: $sql");

		return $result;
	}

	private function tryQuery(string $sql, mixed $params): ?QueryResult
	{
		$query = $this->loadSql($sql);

		if (is_null($params))
		{
			return QueryResult::from($this->db->query($query));
		}

		$stmt = $this->db->prepare($query);
		if (!$stmt)
		{
			throw new \Exception("Failed to prepare: $sql");
		}

		if (is_scalar($params))
		{
			$stmt->bindValue(1, $params);
		}
		else if (is_array($params))
		{
			if (array_is_list($params))
			{
				for ($i = 0; $i < count($params); ++$i)
				{
					$stmt->bindValue($i+1, $params[$i]);
				}
			}
			else
			{
				foreach ($params as $key => $val)
				{
					$stmt->bindValue($key, $val);
				}
			}
		}

		return QueryResult::from($stmt->execute());
	}

	// query a single row that may or may not exist
	public function queryRow(string $sql, mixed $params = null, int $mode = SQLITE3_ASSOC): ?array
	{
		$result = $this->query($sql, $params);
		$count = 0;
		$result_row = null;

		foreach ($result->rows($mode) as $row)
		{
			++$count;
			$result_row = $row;
		}

		if ($count > 1)
		{
			throw new \Exception("Expected at most 1 row in query result");
		}

		return $result_row;
	}

	public function queryValue(string $sql, mixed $params = null): mixed
	{
		$row = $this->queryRow($sql, $params, SQLITE3_NUM);
		if (!$row) return null;
		return $row[0] ?? null;
	}

	private static function transactionType(TransactionType $type): string
	{
		switch ($type)
		{
			case TransactionType::Deferred:
				return 'DEFERRED';
			case TransactionType::Immediate:
				return 'IMMEDIATE';
			case TransactionType::Exclusive:
				return 'EXCLUSIVE';
			default:
				throw new \Exception("Invalid transaction type: $type");
		}
	}
}
