UPDATE palette
SET
	name=:name,
	save_token=:save_token
WHERE id=:id;
