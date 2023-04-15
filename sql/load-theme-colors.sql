SELECT 
	id,
	name,
	palette_color,
	lightness,
	system_color
FROM theme_color
WHERE theme=?;
