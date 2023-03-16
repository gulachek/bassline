<link rel="stylesheet" href="/assets/login_page.css" />

<script src="https://accounts.google.com/gsi/client" async defer></script>

<h1> Log in </h1>

<nav-tab class="tab-strip">
<?php foreach ($TEMPLATE['plugins'] as $key => $plugin): ?>
	<tab-item key="<?=text($key)?>" title="<?=text($plugin->title())?>">
		<?php $plugin->invokeRenderLoginForm($key); ?>
	</tab-item>
<?php endforeach; ?>
</nav-tab>
