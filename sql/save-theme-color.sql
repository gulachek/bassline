UPDATE theme_color
SET
	name=:name,
	palette_color=:palette_color,
	lightness=:lightness
WHERE id=:id AND theme=:theme; -- redundant validation to make sure saving to correct theme
