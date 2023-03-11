UPDATE theme_color
SET fg_color = other.fg_color
FROM (
	theme_color AS self  INNER JOIN theme_color AS other ON self.theme = other.theme
)
WHERE self.fg_color = ? AND self.fg_color <> other.fg_color
