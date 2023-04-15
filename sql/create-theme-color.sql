INSERT INTO theme_color (theme, palette_color)
SELECT t.id, pc.id FROM palette_color as pc
INNER JOIN palette as p ON pc.palette = p.id
INNER JOIN theme as t ON t.palette = p.id
WHERE t.id = ?
LIMIT 1;
