INSERT INTO group_membership (user_id,group_id)
SELECT id, coalesce(:group, primary_group) FROM users
WHERE id = :user;
