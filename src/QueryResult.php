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
}
