UPDATE theme_color_map
SET theme_color=self.theme_color
FROM theme_color_map AS self
WHERE
	theme_color_map.theme_color=?
	AND theme_color_map.theme_color <> self.theme_color
