DELETE FROM group_capabilities
WHERE cap_id NOT IN (SELECT id FROM capabilities);
