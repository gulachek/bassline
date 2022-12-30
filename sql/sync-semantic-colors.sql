INSERT OR IGNORE INTO theme_color_map (theme, semantic_color)
SELECT
	T.id as theme,
	S.id as semantic_color
FROM theme as T
CROSS JOIN semantic_color as S
WHERE semantic_color NOT IN (
	SELECT semantic_color FROM theme_color_map
);

DELETE FROM theme_color_map
WHERE semantic_color NOT IN (SELECT id FROM semantic_color);
