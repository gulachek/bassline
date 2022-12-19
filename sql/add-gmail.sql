INSERT OR IGNORE INTO google_auth (gmail_address, user_id)
	VALUES (:email, :id);
