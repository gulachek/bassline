<link rel="stylesheet" href="/assets/nonce_login_form.css" />

<form method="POST" action="<?=text($POST_URI)?>">
	<textarea name="nonce" maxlength="345" rows="4" cols="32"
	></textarea> <br />
	<input type="submit" value="Log in" />
</form>
<p>
	Enter a token that was given to you by an administrator to log in.
</p>
