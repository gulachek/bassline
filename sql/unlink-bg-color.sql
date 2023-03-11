UPDATE theme_color
SET bg_color = other.bg_color
FROM (
	theme_color AS self  INNER JOIN theme_color AS other ON self.theme = other.theme
)
WHERE self.bg_color = ? AND self.bg_color <> other.bg_color
