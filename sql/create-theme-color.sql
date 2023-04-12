INSERT INTO theme_color (theme, color)
SELECT t.id, c.id FROM color as c
INNER JOIN palette as p ON c.palette = p.id
INNER JOIN theme as t ON t.palette = p.id
WHERE t.id = ?
LIMIT 1;
