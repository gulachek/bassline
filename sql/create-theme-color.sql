INSERT INTO theme_color (theme, fg_color, bg_color)
SELECT t.id, c.id, c.id FROM color as c
INNER JOIN palette as p ON c.palette = p.id
INNER JOIN theme as t ON t.palette = p.id
WHERE t.id = ?
LIMIT 1;
