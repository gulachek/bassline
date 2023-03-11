INSERT INTO theme_color_map (theme, semantic_color, theme_color)
SELECT :theme, id, :theme_color FROM semantic_color;
