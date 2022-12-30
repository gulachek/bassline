UPDATE theme_color
SET
	name=:name,
	bg_color=:bg_color,
	bg_lightness=:bg_lightness,
	fg_color=:fg_color,
	fg_lightness=:fg_lightness
WHERE id=:id AND theme=:theme; -- redundant validation to make sure saving to correct theme
