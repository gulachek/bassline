:root {
	color-scheme: light dark;

<?php foreach ($LIGHT as $name => $color): ?>
	--theme-<?=$name?>: <?=$color?>;
<?php endforeach; ?>
}

@media (prefers-color-scheme: dark) {
	:root {
<?php foreach ($DARK as $name => $color): ?>
		--theme-<?=$name?>: <?=$color?>;
<?php endforeach; ?>
	}
}
