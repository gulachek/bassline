INSERT INTO nonce_auth (user_id, nonce, expiration)
VALUES (:user_id, :nonce, datetime('now', '+5 minutes'));
