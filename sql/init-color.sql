CREATE TABLE props (
	name TEXT UNIQUE,
	value TEXT
);

INSERT INTO props (name, value)
VALUES ("version", "0.1.0");

-- Color palette is group of named colors/hex codes
CREATE TABLE palette (
	id INTEGER PRIMARY KEY,
	name TEXT NOT NULL DEFAULT "New Palette"
);

CREATE TABLE color (
	id INTEGER PRIMARY KEY,
	name TEXT NOT NULL DEFAULT "New Color",
	hex TEXT NOT NULL DEFAULT "#000000", -- #rrggbb
	palette INTEGER NOT NULL
);

-- this is maintained by app installation: named slots for app colors
CREATE TABLE semantic_color (
	id INTEGER PRIMARY KEY,
	app TEXT NOT NULL,
	name TEXT NOT NULL
);

-- Theme is mapping named app-defined colors to palette
CREATE TABLE theme (
	id INTEGER PRIMARY KEY,
	name TEXT NOT NULL DEFAULT "New Theme",
	palette INTEGER NOT NULL
);

CREATE TABLE theme_color (
	id INTEGER PRIMARY KEY,
	name TEXT NOT NULL DEFAULT "New Color",
	css_name TEXT, -- can be referenced by css code
	theme INTEGER NOT NULL,
	color INTEGER NOT NULL, -- id in color table
	lightness REAL DEFAULT 0.5
);

-- this is the actual mapping from the color to the app semantic color
CREATE TABLE theme_color_map (
	id INTEGER PRIMARY KEY,
	theme INTEGER NOT NULL,
	semantic_color INTEGER NOT NULL,
	theme_color INTEGER NOT NULL
);
