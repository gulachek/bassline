UPDATE theme_color as self
SET color = tc.color
FROM (
	theme_color AS tc
)
WHERE self.color = ? AND self.color <> tc.color AND self.theme = tc.theme
