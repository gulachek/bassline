SELECT
	T.id as id,
	S.app as app,
	S.name as name,
	T.theme_color as theme_color
FROM theme_color_map as T
INNER JOIN app_color as S ON T.app_color = S.id
WHERE T.theme=?;
