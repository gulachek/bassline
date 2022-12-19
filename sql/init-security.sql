CREATE TABLE IF NOT EXISTS props (
	name TEXT NOT NULL UNIQUE,
	value TEXT
);

INSERT OR IGNORE INTO props (name, value) VALUES ("version", "0.1.0");

CREATE TABLE IF NOT EXISTS users (
	id INT PRIMARY KEY,
	username TEXT UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS google_auth (
	gmail_address TEXT UNIQUE NOT NULL,
	google_user_id TEXT UNIQUE,
	user_id INT NOT NULL
);

CREATE TABLE IF NOT EXISTS login (
	id INT NOT NULL,
	token TEXT UNIQUE NOT NULL,
	expiration TEXT NOT NULL
);
