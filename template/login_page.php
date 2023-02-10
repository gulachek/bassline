<?php require_once $UTIL; ?>
<link rel="stylesheet" href="/static/login_page.css" />

<script src="https://accounts.google.com/gsi/client" async defer></script>
<script src="/static/nav_tab.js"></script>

<h1> Log in </h1>

<p> Choose an authentication method. </p>

<nav-tab class="tab-strip">
<?php foreach ($TEMPLATE['plugins'] as $key => $plugin): ?>
	<tab-item key="<?=text($key)?>" title="<?=text($plugin->title())?>">
		<?php $plugin->invokeRenderLoginForm($key); ?>
	</tab-item>
<?php endforeach; ?>
</nav-tab>
