UPDATE theme_color AS self
SET
	bg_color=c.id,
	fg_color=c.id
FROM (
	theme_color AS tc
	INNER JOIN theme AS t ON tc.theme = t.id
	INNER JOIN color AS c ON t.palette = c.palette
)
WHERE
	self.theme=? AND self.theme=tc.theme
