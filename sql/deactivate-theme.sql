DELETE FROM props
WHERE name LIKE "active-%-theme" AND value = ?;
