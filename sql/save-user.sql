UPDATE users
SET username=:username, is_superuser=:is_superuser, primary_group=:primary_group
WHERE id=:id;
