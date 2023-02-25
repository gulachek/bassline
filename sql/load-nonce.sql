SELECT user_id FROM nonce_auth
WHERE nonce = ? AND (unixepoch(expiration) - unixepoch() > 0);
