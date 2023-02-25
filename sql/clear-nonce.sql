DELETE FROM nonce_auth
WHERE nonce=? OR (unixepoch(expiration) <= unixepoch());
