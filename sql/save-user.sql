UPDATE users
SET username=:username, is_superuser=:is_superuser
WHERE id=:id;
