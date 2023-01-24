SELECT
	name
FROM capabilities AS c
INNER JOIN group_capabilities AS gc ON c.id = gc.cap_id
INNER JOIN group_membership AS gm ON gc.group_id = gm.group_id
WHERE c.app = :app AND gm.user_id = :user_id;
