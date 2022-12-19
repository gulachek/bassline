<script src="https://accounts.google.com/gsi/client" async defer></script>

<h1> Log in </h1>

<div id="g_id_onload"
 data-client_id="<?= $GOOGLE_CLIENT_ID ?>"
 data-login_uri="/login/sign_in_with_google?redirect_uri=<?= urlencode($REFERER) ?>"
 data-auto_prompt="false">
</div>
<div class="g_id_signin"
 data-type="standard"
 data-size="large"
 data-text="sign_in_with"
 data-shape="rectangular"
 data-logo_alignment="center">
</div>

<p>
This website doesn't store any information about your passwords or
require you to make a new one.  Just sign in with your existing Google account
and it's all managed by Google.
</p>

</form>
