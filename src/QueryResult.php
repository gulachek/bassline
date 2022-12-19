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

	public function singleRow(int $mode = SQLITE3_ASSOC): ?array
	{
		$count = 0;
		$result = null;
		foreach ($this->rows($mode) as $row)
		{
			++$count;
			$result = $row;
		}

		if ($count > 1)
		{
			throw new \Exception("Expected at most 1 row in query result");
		}

		return $result;
	}
}
