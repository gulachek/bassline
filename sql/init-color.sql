CREATE TABLE props (
	name TEXT UNIQUE,
	value TEXT
);

INSERT INTO props (name, value)
VALUES ("version", "0.1.0");

-- Color palette is group of named colors/hex codes
CREATE TABLE palette (
	id INTEGER PRIMARY KEY,
	name TEXT NOT NULL DEFAULT "New Palette",
	save_token TEXT
);

CREATE TABLE palette_color (
	id INTEGER PRIMARY KEY,
	name TEXT NOT NULL DEFAULT "New Color",
	hex TEXT NOT NULL DEFAULT "#000000", -- #rrggbb
	palette INTEGER NOT NULL
);

-- this is mantained by bassline installation, not apps
CREATE TABLE system_color  (
	id INTEGER PRIMARY KEY,
	css_name TEXT UNIQUE NOT NULL,
	dark_css_value TEXT NOT NULL, -- default when no theme selected
	light_css_value TEXT NOT NULL,
	default_light_lightness REAL NOT NULL,
	default_dark_lightness REAL NOT NULL,
	description TEXT NOT NULL
);

-- this is maintained by app installation: named slots for app colors
CREATE TABLE app_color (
	id INTEGER PRIMARY KEY,
	app TEXT NOT NULL,
	name TEXT NOT NULL,
	system_color INTEGER NOT NULL
);

CREATE UNIQUE INDEX app_color_index ON app_color(app, name);

-- Theme is mapping named app-defined colors to palette
CREATE TABLE theme (
	id INTEGER PRIMARY KEY,
	name TEXT NOT NULL DEFAULT "New Theme",
	palette INTEGER NOT NULL,
	save_token TEXT
);

CREATE TABLE theme_color (
	id INTEGER PRIMARY KEY,
	name TEXT NOT NULL DEFAULT "New Color",
	system_color INTEGER, -- non-null means this is a system color
	theme INTEGER NOT NULL,
	palette_color INTEGER NOT NULL,
	lightness REAL DEFAULT 0.5
);

-- this is the actual mapping from the color to the app app color
CREATE TABLE theme_color_map (
	id INTEGER PRIMARY KEY,
	theme INTEGER NOT NULL,
	app_color INTEGER NOT NULL,
	theme_color INTEGER NOT NULL
);

CREATE UNIQUE INDEX theme_app_color ON theme_color_map(theme, app_color);
