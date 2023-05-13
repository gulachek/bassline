UPDATE groups
SET
	groupname=:groupname,
	save_token=:save_token
WHERE id=:id;
