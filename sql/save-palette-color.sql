UPDATE palette_color
SET
	name=:name,
	hex=:hex
WHERE id=:id;
