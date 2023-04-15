UPDATE theme_color AS self
SET
	palette_color=pc.id
FROM (
	theme_color AS tc
	INNER JOIN theme AS t ON tc.theme = t.id
	INNER JOIN palette_color AS pc ON t.palette = pc.palette
)
WHERE
	self.theme=? AND self.theme=tc.theme
