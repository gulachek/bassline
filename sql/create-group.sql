WITH new_groups AS (
	SELECT CAST(substr(groupname,10) AS INTEGER) AS uniq FROM groups
	WHERE groupname LIKE 'new_group%'
	UNION
	SELECT -1 AS uniq
	ORDER BY uniq DESC
	LIMIT 1
)
INSERT INTO groups (groupname)
SELECT
	CASE MAX(uniq)
		WHEN -1 THEN ('new_group')
		ELSE ('new_group' || (MAX(uniq)+1))
	END
FROM new_groups;
