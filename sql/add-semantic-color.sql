WITH
	new_color(app, color_name, css_name) AS
	(VALUES (:app, :color_name, :sys_color))
INSERT INTO semantic_color (app, name, system_color)
SELECT nc.app, nc.color_name, sys.id
FROM new_color AS nc
INNER JOIN system_color AS sys
ON nc.css_name = sys.css_name
