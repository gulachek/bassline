<?php
require $UTIL;

function blLinkItem(string $href, string $text, string $iconPath)
{
?>
	<a href="<?= text($href) ?>" class="item" title="<?= text($text) ?>">
		<span class="icon"> <?php include $iconPath; ?> </span>
		<span class="item-text"> <?= text($text) ?> </span>
	</a>
<?php
}

?>
<!DOCTYPE html>
<html>

<head>
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title> <?= text($TITLE) ?> </title>

	<script src="/assets/components.js"></script>

	<link rel="stylesheet" type="text/css" href="/shell/theme.css?app=shell" />
	<link rel="stylesheet" type="text/css" href="/shell/theme.css?app=<?= text($APP) ?>" />
	<link rel="stylesheet" type="text/css" href="/assets/main.css" />
</head>

<body>
	<nav class="nav-bar">

		<div class="left">
			<?php blLinkItem("/", $SITE_NAME, __DIR__ . '/svg/home.svg'); ?>
			<?php foreach ($APPS as $appHref => $app) : ?>
				<?php blLinkItem("/$appHref/", $app->title(), $app->iconPath()); ?>
			<?php endforeach; ?>
		</div>

		<?php if (isset($USER)) : ?>
			<div class="right">
				<div class="item menu">
					<span class="icon"> <?php include __DIR__ . '/svg/gear.svg' ?> </span>
					<span class="item-text username"><?= text($USERNAME) ?></span>
					<div class="items popup">
						<?php if ($SHOW_ADMIN_LINK) : ?>
							<?php blLinkItem("/site/admin/", "Admin", __DIR__ . '/svg/screwdriver-wrench.svg'); ?>
						<?php endif; ?>
						<?php blLinkItem("/logout/", "Log out", __DIR__ . '/svg/right-from-bracket.svg'); ?>
					</div>
				</div>
			</div>
		<?php else : ?>
			<div class="right">
				<?php blLinkItem("/login/", "Log in", __DIR__ . '/svg/right-to-bracket.svg'); ?>
			</div>
		<?php endif; ?>

	</nav>

	<main class="main <?= text($LAYOUT_CLASSNAME) ?>">
		<?php $RENDER_BODY(); ?>
	</main>

</body>

</html>
