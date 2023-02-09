CREATE TABLE props (
	name TEXT NOT NULL UNIQUE,
	value TEXT
);

CREATE TABLE users (
	id INTEGER PRIMARY KEY,
	username TEXT UNIQUE NOT NULL,
	is_superuser INTEGER NOT NULL DEFAULT 0, -- user can do anything
	primary_group INTEGER NOT NULL
);

CREATE TABLE groups (
	id INTEGER PRIMARY KEY,
	groupname TEXT UNIQUE NOT NULL
);

-- Primary group is redundant for query simplicity
CREATE TABLE group_membership (
	user_id INTEGER NOT NULL,
	group_id INTEGER NOT NULL
);

-- Application-defined capabilities (defined via code)
CREATE TABLE capabilities (
	id INTEGER PRIMARY KEY,
	app TEXT NOT NULL,
	name TEXT NOT NULL
	);

CREATE TABLE group_capabilities (
	group_id INTEGER NOT NULL,
	cap_id INTEGER NOT NULL
);

CREATE TABLE google_auth (
	gmail_address TEXT UNIQUE NOT NULL,
	google_user_id TEXT UNIQUE,
	user_id INTEGER NOT NULL
);

CREATE TABLE login (
	id INTEGER NOT NULL, -- user id
	token TEXT UNIQUE NOT NULL,
	expiration TEXT NOT NULL
);
