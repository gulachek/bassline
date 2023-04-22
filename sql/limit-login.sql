WITH here_to_stay(i) AS (
	SELECT rowid FROM login
	WHERE id = :id AND unixepoch(expiration) > unixepoch()
	ORDER BY expiration DESC
	LIMIT :limit
)
DELETE FROM login
WHERE rowid IN (
	SELECT rowid FROM login WHERE id = :id
	EXCEPT
	SELECT i FROM here_to_stay
)
