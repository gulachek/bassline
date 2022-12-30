:root {
	color-scheme: light dark;

<?php foreach ($LIGHT as $name => $colors): ?>
	--theme-<?=$name?>-bg: <?=$colors['bg']?>;
	--theme-<?=$name?>-fg: <?=$colors['fg']?>;
<?php endforeach; ?>
}

@media (prefers-color-scheme: dark) {
	:root {
<?php foreach ($DARK as $name => $colors): ?>
		--theme-<?=$name?>-bg: <?=$colors['bg']?>;
		--theme-<?=$name?>-fg: <?=$colors['fg']?>;
<?php endforeach; ?>
	}
}
