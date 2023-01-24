SELECT
	users.id AS id
FROM users
INNER JOIN login ON users.id = login.id
WHERE token=? AND expiration > datetime('now');
