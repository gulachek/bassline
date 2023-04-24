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

	public function mountNamedQueries(string $query_dir): void
	{
		$this->query_dir = $query_dir;
	}

	public function lock(TransactionType $type = TransactionType::Exclusive): bool
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

	private function loadSql(string $sql): string
	{
		return file_get_contents("{$this->query_dir}/$sql.sql");
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
}
