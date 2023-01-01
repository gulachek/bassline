<?php
function esc(string $s): string
{
	return htmlspecialchars($s);
}
?>
<!DOCTYPE html>
<html>
<head>

<meta name="viewport" content="width=device-width, initial-scale=1" />
<title> <?= $SHELL->title() ?> </title>

<script src="/static/loading_overlay.js"></script>

<link rel="stylesheet" type="text/css" href="/shell/theme.css?app=shell" />

<?php foreach ($SHELL->stylesheets() as $style): ?>
	<link rel="stylesheet" type="text/css" href="<?= $style ?>" />
<?php endforeach; ?>

</head>

<body>

<nav class="nav-bar">

<a href="/"> <?= $SITE_NAME ?> </a>

<?php foreach ($APPS as $appHref => $app): ?>
	<a href="/<?= esc($appHref) ?>/"> <?= esc($app->title()) ?> </a>
<?php endforeach; ?>

<?php if (isset($USER)): ?>
	<!-- <div onclick="void(0);" class="menu"> -->
	<div class="menu login">
	<span><?= esc($USERNAME) ?></span>
	<div class="items">
	<a href="/logout/"> Log out </a>
	</div>
	</div>
<?php else: ?>
	<a href="/login/" class="login"> Log in </a>
<?php endif; ?>

</nav>

<main class="main">
	<loading-overlay>
		<?= $SHELL->mainBody() ?>
	</loading-overlay>
</main>

</body>
</html>
