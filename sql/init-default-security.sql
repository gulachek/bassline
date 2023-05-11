INSERT INTO groups (groupname) VALUES ('staff');

INSERT INTO users (username, primary_group, is_superuser)
SELECT 'admin', id, 1 FROM groups WHERE groupname = 'staff';

INSERT INTO group_membership (user_id, group_id)
SELECT id, primary_group FROM users WHERE username = 'admin';
