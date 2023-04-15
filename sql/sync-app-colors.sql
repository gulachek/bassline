INSERT INTO theme_color_map (theme, app_color, theme_color)
SELECT
	T.id,
	S.id,
	TC.id
FROM theme as T
CROSS JOIN app_color as S
INNER JOIN theme_color as TC ON TC.theme = T.id AND TC.system_color = S.system_color
EXCEPT
SELECT theme, app_color, theme_color FROM theme_color_map;

DELETE FROM theme_color_map
WHERE app_color NOT IN (SELECT id FROM app_color);
