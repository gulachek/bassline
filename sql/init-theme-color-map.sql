INSERT INTO theme_color_map (theme, app_color, theme_color)
SELECT :theme, id, :theme_color FROM app_color;
