<?php

namespace Shell;

require __DIR__ . '/../vendor/autoload.php';

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

	public function lastInsertRowID(): int
	{
		return $this->db->lastInsertRowID();
	}

	// MAKE SURE YOU DON'T LET THE USER SPECIFY THE PARAMETERS
	public function loadRowUnsafe(string $table, int $rowid): array
	{
		return $this->db->querySingle("SELECT * FROM $table WHERE rowid=$rowid;", true);
	}

	private function loadSql(string $sql): string
	{
		return file_get_contents("{$this->query_dir}/$sql.sql");
	}

	public function exec(string $sql): bool
	{
		return $this->db->exec($this->loadSql($sql));
	}

	public function query(string $sql, mixed $params = null): ?QueryResult
	{
		$query = $this->loadSql($sql);

		if (!$params)
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

	public function queryRow(string $sql, mixed $params = null, int $mode = SQLITE3_ASSOC): ?array
	{
		return $this->query($sql, $params)?->singleRow($mode);
	}

	public function queryValue(string $sql, mixed $params = null): mixed
	{
		$row = $this->queryRow($sql, $params, SQLITE3_NUM);
		if (!$row) return null;
		return $row[0] ?? null;
	}
}
