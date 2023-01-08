<?php require_once $UTIL; ?>
<link rel="stylesheet" href="/static/login_page.css" />

<script src="https://accounts.google.com/gsi/client" async defer></script>
<script src="/static/nav_tab.js"></script>

<h1> Log in </h1>

<p> Choose an authentication method. </p>

<nav-tab>
	<tab-item title="Sign in with Google">
		<div id="g_id_onload"
		 data-client_id="<?=text($TEMPLATE['google_client_id'])?>"
		 data-login_uri="<?=text($TEMPLATE['siwg_request_uri'])?>"
		 data-auto_prompt="false">
		</div>
		<div class="g_id_signin"
		 data-type="standard"
		>
		</div>

		<p>
			Log in with your gmail account.  If prompted, make sure you allow the website to know your
			email address, otherwise there will be no way to verify your email is accurate.
		</p>
	</tab-item>
	<tab-item title="No Auth">
		<form method="POST" action="/shell/log_in_as_user">
			<input type="hidden" name="redirect-uri" value="<?=text($TEMPLATE['referrer'])?>" />
			<label> user id:
				<input type="number" step="1" min="0" value="0" name="user-id" />
			</label>
			<input type="submit" value="Log in" />
		</form>
		<p>
			You are who you say you are. I trust you no matter what.
		</p>
	</tab-item>
</nav-tab>
