UPDATE theme_color as self
SET palette_color = tc.palette_color
FROM (
	theme_color AS tc
)
WHERE self.palette_color = ? AND self.palette_color <> tc.palette_color AND self.theme = tc.theme
