<?php require $UTIL; ?>
<!DOCTYPE html>
<html>

<head>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title> <?=text($TITLE)?> </title>

<script src="/assets/components.js"></script>

<link rel="stylesheet" type="text/css" href="/shell/theme.css?app=shell" />
<link rel="stylesheet" type="text/css" href="/assets/main.css" />
</head>

<body>
<nav class="nav-bar">

<a href="/"> <?= text($SITE_NAME) ?> </a>

<?php foreach ($APPS as $appHref => $app): ?>
	<a href="/<?= text($appHref) ?>/"> <?= text($app->title()) ?> </a>
<?php endforeach; ?>

<?php if (isset($USER)): ?>
	<div class="menu">
		<span class="username"><?= text($USERNAME) ?></span>
		<div class="items">
			<?php if ($SHOW_ADMIN_LINK): ?>
				<a href="/site/admin/"> Admin </a>
			<?php endif; ?>
			<a href="/logout/"> Log out </a>
		</div>
	</div>
<?php else: ?>
	<a href="/login/" class="login"> Log in </a>
<?php endif; ?>

</nav>

<main class="main">
	<loading-overlay>
		<?php $RENDER_BODY(); ?>
	</loading-overlay>
</main>

</body>
</html>
