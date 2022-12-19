DELETE FROM login
WHERE rowid NOT IN (
	SELECT rowid
	FROM login
	WHERE id = :id
	ORDER BY expiration DESC
	LIMIT :limit
);
