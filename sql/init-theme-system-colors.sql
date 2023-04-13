WITH
	first_color(id) AS (
		SELECT color.id FROM color
		INNER JOIN theme ON color.palette = theme.palette
		WHERE theme.id = :theme
		LIMIT 1
	),
	sys_colors(id, css_name, lightness) AS (
		SELECT id, css_name,
		CASE
			WHEN :is_dark THEN default_dark_lightness
			ELSE default_light_lightness
		END
		FROM system_color
	)
INSERT INTO theme_color (name, system_color, theme, color, lightness)
SELECT sys.css_name, sys.id, :theme, pc.id, sys.lightness
FROM sys_colors AS sys
CROSS JOIN first_color AS pc -- palette color
