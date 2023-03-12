UPDATE theme_color
SET
	bg_color=c.id,
	fg_color=c.id
FROM (
	theme_color AS self
	INNER JOIN theme AS t ON self.theme = t.id
	INNER JOIN color AS c ON t.palette = c.palette
)
WHERE
	self.theme=?;
