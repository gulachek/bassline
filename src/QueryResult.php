<?php

namespace Shell;

class QueryResult
{
	public function __construct(
		private \SQLite3Result $result
	)
	{
	}

	public static function from(\SQLite3Result|false $result): ?QueryResult
	{
		if (!$result)
			return null;

		return new QueryResult($result);
	}

	public function rows(int $mode = SQLITE3_ASSOC): \Generator
	{
		while ($row = $this->result->fetchArray($mode))
		{
			yield $row;
		}
	}

	// assume there exists an identifying column in query result
	// this will iterate over rows and fill an array with keys
	// of that column's value pointing to the row
	public function indexBy(string|int $column): array
	{
		$mode = is_string($column) ? SQLITE3_ASSOC : SQLITE3_NUM;
		$out = [];
		foreach ($this->rows($mode) as $row)
		{
			$out[$row[$column]] = $row;
		}
		return $out;
	}

	// so common that it deserves function
	public function indexById(): array
	{
		return $this->indexBy('id');
	}
}
