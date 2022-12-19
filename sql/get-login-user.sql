SELECT
	users.id AS id,
	users.username AS username
FROM users
INNER JOIN login ON users.id = login.id
WHERE token=? AND expiration > datetime('now');
