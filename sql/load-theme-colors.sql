SELECT 
	id,
	name,
	bg_color,
	bg_lightness,
	fg_color,
	fg_lightness
FROM theme_color
WHERE theme=?;
