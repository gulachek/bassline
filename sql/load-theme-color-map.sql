SELECT
	T.id as id,
	S.app as app,
	S.name as name,
	T.theme_color as theme_color
FROM theme_color_map as T
INNER JOIN semantic_color as S ON T.semantic_color = S.id
WHERE T.theme=?;
