CREATE TABLE IF NOT EXISTS props (
	name TEXT NOT NULL UNIQUE,
	value TEXT
);

INSERT OR IGNORE INTO props (name, value) VALUES ("version", "0.1.0");

CREATE TABLE IF NOT EXISTS users (
	id INTEGER PRIMARY KEY,
	username TEXT UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS google_auth (
	gmail_address TEXT UNIQUE NOT NULL,
	google_user_id TEXT UNIQUE,
	user_id INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS login (
	id INTEGER NOT NULL, -- user id
	token TEXT UNIQUE NOT NULL,
	expiration TEXT NOT NULL
);
