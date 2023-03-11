INSERT OR IGNORE INTO theme_color_map (theme, semantic_color, theme_color)
SELECT
	T.id,
	S.id,
	MIN(TC.id)
FROM theme as T
CROSS JOIN semantic_color as S
INNER JOIN theme_color as TC ON TC.theme = T.id
GROUP BY T.id, S.id
HAVING S.id NOT IN (
	SELECT semantic_color FROM theme_color_map
);

DELETE FROM theme_color_map
WHERE semantic_color NOT IN (SELECT id FROM semantic_color);
