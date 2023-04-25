-- CREATE TEMP TABLE src_colors(app T, name T, system_color_css_name T)
CREATE TEMP TABLE deleted_app_colors AS
	SELECT installed.id FROM app_color AS installed
	LEFT JOIN src_colors AS src
	ON installed.app = src.app AND installed.name = src.name
	WHERE src.app IS NULL
;

INSERT OR REPLACE INTO app_color (app, name, system_color)
	SELECT src.app, src.name, sys.id FROM src_colors AS src
	INNER JOIN system_color AS sys ON src.system_color_css_name = sys.css_name
;

DELETE FROM theme_color_map
WHERE app_color IN (SELECT id FROM deleted_app_colors);

DELETE FROM app_color
WHERE id IN (SELECT id FROM deleted_app_colors);

DROP TABLE deleted_app_colors;

INSERT OR IGNORE INTO theme_color_map (theme, app_color, theme_color)
	SELECT t.id, app.id, tc.id FROM theme AS t
	CROSS JOIN app_color AS app
	INNER JOIN theme_color AS tc
		ON app.system_color = tc.system_color AND tc.theme = t.id;

DROP TABLE src_colors;
