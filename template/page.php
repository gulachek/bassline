<?php require $UTIL; ?>
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
			<a href="/" class="item">
				<svg xmlns="http://www.w3.org/2000/svg" class="icon" height="1em" viewBox="0 0 576 512"><!--! Font Awesome Free 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. -->
					<path d="M575.8 255.5c0 18-15 32.1-32 32.1h-32l.7 160.2c0 2.7-.2 5.4-.5 8.1V472c0 22.1-17.9 40-40 40H456c-1.1 0-2.2 0-3.3-.1c-1.4 .1-2.8 .1-4.2 .1H416 392c-22.1 0-40-17.9-40-40V448 384c0-17.7-14.3-32-32-32H256c-17.7 0-32 14.3-32 32v64 24c0 22.1-17.9 40-40 40H160 128.1c-1.5 0-3-.1-4.5-.2c-1.2 .1-2.4 .2-3.6 .2H104c-22.1 0-40-17.9-40-40V360c0-.9 0-1.9 .1-2.8V287.6H32c-18 0-32-14-32-32.1c0-9 3-17 10-24L266.4 8c7-7 15-8 22-8s15 2 21 7L564.8 231.5c8 7 12 15 11 24z" />
				</svg>
				<span class="item-text"> <?= text($SITE_NAME) ?> </span>
			</a>
			<?php foreach ($APPS as $appHref => $app) : ?>
				<a class="item" href="/<?= text($appHref) ?>/">
					<svg xmlns="http://www.w3.org/2000/svg" class="icon" height="1em" viewBox="0 0 576 512"><!--! Font Awesome Free 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. -->
						<path d="M575.8 255.5c0 18-15 32.1-32 32.1h-32l.7 160.2c0 2.7-.2 5.4-.5 8.1V472c0 22.1-17.9 40-40 40H456c-1.1 0-2.2 0-3.3-.1c-1.4 .1-2.8 .1-4.2 .1H416 392c-22.1 0-40-17.9-40-40V448 384c0-17.7-14.3-32-32-32H256c-17.7 0-32 14.3-32 32v64 24c0 22.1-17.9 40-40 40H160 128.1c-1.5 0-3-.1-4.5-.2c-1.2 .1-2.4 .2-3.6 .2H104c-22.1 0-40-17.9-40-40V360c0-.9 0-1.9 .1-2.8V287.6H32c-18 0-32-14-32-32.1c0-9 3-17 10-24L266.4 8c7-7 15-8 22-8s15 2 21 7L564.8 231.5c8 7 12 15 11 24z" />
					</svg>
					<span class="item-text"> <?= text($app->title()) ?> </span>
				</a>
			<?php endforeach; ?>
		</div>

		<?php if (isset($USER)) : ?>
			<div class="right">
				<div class="item menu">
					<span class="item-text username"><?= text($USERNAME) ?></span>
					<div class="items popup">
						<?php if ($SHOW_ADMIN_LINK) : ?>
							<a class="item" href="/site/admin/">
								<span class="item-text"> Admin </span>
							</a>
						<?php endif; ?>
						<a class="item" href="/logout/">
							<span class="item-text"> Log out </span>
						</a>
					</div>
				</div>
			</div>
		<?php else : ?>
			<div class="right">
				<a href="/login/" class="login item">
					<svg xmlns="http://www.w3.org/2000/svg" class="icon" height="1em" viewBox="0 0 448 512"><!--! Font Awesome Free 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. -->
						<path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z" />
					</svg>
					<span class="item-text"> Log in </span>
				</a>
			</div>
		<?php endif; ?>

	</nav>

	<main class="main <?= text($LAYOUT_CLASSNAME) ?>">
		<?php $RENDER_BODY(); ?>
	</main>

</body>

</html>
