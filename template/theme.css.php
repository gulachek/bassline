:root {
	color-scheme: light dark;

<?php foreach ($LIGHT_SYS as $name => $color): ?>
	--system-theme-<?=$name?>: <?=$color?>;
<?php endforeach; ?>

<?php foreach ($LIGHT_APP as $name => $color): ?>
	--theme-<?=$name?>: <?=$color?>;
<?php endforeach; ?>
}

@media (prefers-color-scheme: dark) {
	:root {
<?php foreach ($DARK_SYS as $name => $color): ?>
		--system-theme-<?=$name?>: <?=$color?>;
<?php endforeach; ?>

<?php foreach ($DARK_APP as $name => $color): ?>
		--theme-<?=$name?>: <?=$color?>;
<?php endforeach; ?>
	}
}
