INSERT INTO login (id, token, expiration)
	VALUES (:id, :token, datetime('now', '+30 days'));
