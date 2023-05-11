WITH new_users AS (
	SELECT CAST(substr(username,9) AS INTEGER) AS uniq FROM users
	WHERE username LIKE 'new_user%'
	UNION
	SELECT -1 AS uniq
	ORDER BY uniq DESC
	LIMIT 1
)
INSERT INTO users (username,primary_group)
SELECT
	CASE MAX(uniq)
		WHEN -1 THEN ('new_user')
		ELSE ('new_user' || (MAX(uniq)+1))
	END AS username,
	groups.id AS gid
FROM new_users, groups
GROUP BY groups.id
LIMIT 1;
