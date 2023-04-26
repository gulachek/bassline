<?php

namespace Gulachek\Bassline;

class ColorDatabase
{
	const SHADE_COUNT = 15;

	public function __construct(
		private Database $db
	)
	{
		$this->db->mountNamedQueries(__DIR__ . '/../sql');
	}

	static function fromConfig(Config $config): ColorDatabase
	{
		$path = "{$config->dataDir()}/color.db";
		return new ColorDatabase(new Database(new \Sqlite3($path)));
	}

	public function lock(): bool
	{
		return $this->db->lock();
	}

	public function unlock(): void
	{
		$this->db->unlock();
	}

	private function createTempSrcColors(array $apps): void
	{
		$this->db->exec('temp-src-colors');

		$stmt = $this->db->prepare('insert-src-color');
		foreach ($apps as $key => $app)
		{
			$colors = $app->colors();
			foreach ($colors as $name => $def)
			{
				$stmt->execWith([
					':app' => $key,
					':name' => $name,
					':sys_color' => $def['default-system']
				]);
			}
		}

		$stmt->close();
	}

	public function installWithApps(array $apps): ?string
	{
		if ($err = $this->initReentrant())
			return $err;

		$this->createTempSrcColors($apps);

		$this->db->exec('consume-temp-src-colors');
		return null;
	}

	private function initReentrant(): ?string
	{
		if ($this->db->queryValue('table-exists', 'props'))
		{
			// assume this was already created and we just need to update
			return null;
		}

		$this->db->exec('init-color');
		$this->db->exec('init-system-colors');

		$palette = $this->createPalette('Default');
		foreach ($palette['colors'] as $id => $color)
		{
			$palette['colors'][$id]['name'] = 'Default';
			$palette['colors'][$id]['hex'] = '#11c1e4';
			break;
		}
		$this->savePalette($palette);

		$light = $this->createTheme(isDark: false);
		$light['name'] = 'Default Light';
		$this->saveTheme($light);

		$dark = $this->createTheme(isDark: true);
		$dark['name'] = 'Default Dark';
		$this->saveTheme($dark);

		return null;
	}

	public function loadSystemColorValues(): array
	{
		return $this->db->query('load-system-color-values')->indexById();
	}

	public function availablePalettes(): array
	{
		$result = $this->db->query('get-palettes');
		return $result->indexById();
	}

	public function createPalette(string $name): array
	{
		$this->db->query('create-palette', $name);
		$id = $this->db->lastInsertRowID();
		// empty palette would never be useful
		$this->createPaletteColor($id);
		return $this->loadPalette($id);
	}

	public function loadPalette(int $id): ?array
	{
		$palette = $this->db->queryRow('load-palette', $id);

		if (!$palette)
			return null;

		$result = $this->db->query('load-palette-colors', $id);
		$palette['colors'] = $result->indexById();
		return $palette;
	}

	// create a new color in the palette and return the id of the color
	public function createPaletteColor(int $id): int
	{
		$this->db->query('create-palette-color', $id);
		return $this->db->lastInsertRowID();
	}

	public function deletePaletteColor(int $id): void
	{
		$this->db->query('unlink-theme-color', $id);
		$this->db->query('delete-palette-color', $id);
	}

	public function getPaletteFromColor(int $color_id): int
	{
		return $this->db->queryValue('get-color-palette', $color_id);
	}

	public function savePalette(array $palette): bool
	{
		$this->db->query('save-palette', [
			':id' => $palette['id'],
			':name' => $palette['name'],
			':save_token' => $palette['save_token']
		]);

		foreach ($palette['colors'] as $color)
		{
			$this->db->query('save-palette-color', [
				':id' => $color['id'],
				':name' => $color['name'],
				':hex' => $color['hex']
			]);
		}

		return true;
	}

	public function createTheme(bool $isDark): array
	{
		$this->db->query('create-theme');
		$id = $this->db->lastInsertRowID();
		$this->db->query('init-theme-system-colors', [
			':theme' => $id,
			':is_dark' => $isDark
		]);
		$color = $this->createThemeColor($id);
		$this->db->query('init-theme-color-map', [
			':theme' => $id,
			':theme_color' => $color['id']
		]);
		return $this->loadTheme($id);
	}

	public function loadTheme(int $theme_id): ?array
	{
		$row = $this->db->loadRowUnsafe('theme', $theme_id);

		if (!$row)
			return null;

		$theme = [
			'id' => $row['id'],
			'name' => $row['name'],
			'save_token' => $row['save_token']
		];

		if (is_int($row['palette']))
		{
			if (!$theme['palette'] = $this->loadPalette($row['palette']))
				throw new \Exception("theme $theme_id has a corrupt palette id {$row['palette']}");
		}

		// load theme colors
		$result = $this->db->query('load-theme-colors', $theme_id);
		$theme['themeColors'] = $result->indexById();

		// load color mappings
		$result = $this->db->query('load-theme-color-map', $theme_id);
		$theme['mappings'] = $result->indexById();

		return $theme;
	}

	public function saveTheme(array $theme): void
	{
		$this->db->query('save-theme', [
			':id' => $theme['id'],
			':name' => $theme['name'],
			':save_token' => $theme['save_token']
		]);

		foreach ($theme['themeColors'] as $id => $theme_color)
		{
			$this->db->query('save-theme-color', [
				':theme' => $theme['id'],
				':id' => $id,
				':name' => $theme_color['name'],
				':palette_color' => $theme_color['palette_color'],
				':lightness' => $theme_color['lightness'],
			]);
		}

		foreach ($theme['mappings'] as $id => $mapping)
		{
			$this->db->query('map-color', [
				':theme' => $theme['id'],
				':id' => $id,
				':theme_color' => $mapping['theme_color']
			]);
		}
	}

	public function changeThemePalette(int $theme_id, int $palette_id): void
	{
		$this->db->query('change-palette', [
			':theme' => $theme_id,
			':palette' => $palette_id
		]);
		$this->db->query('reset-theme-color', $theme_id);
	}

	public function createThemeColor(int $theme_id): array
	{
		$this->db->query('create-theme-color', $theme_id);
		$id = $this->db->lastInsertRowID();
		return $this->db->loadRowUnsafe('theme_color', $id);
	}

	public function deleteThemeColor(int $theme_color_id): void
	{
		$this->db->query('delete-theme-color', $theme_color_id);
		$this->db->query('clear-theme-color-mappings', $theme_color_id);
	}

	public function createColorMapping(int $theme_id, string $app, string $app_color): array
	{
		$this->db->query('create-color-mapping', [
			':theme' => $theme_id,
			':app' => $app,
			':color_name' => $app_color
		]);

		return $this->db->loadRowUnsafe('theme_color', $this->db->lastInsertRowID());
	}

	public function addAppColor(string $app, string $app_color, array $color_def): void
	{
		$color = new Color($app, $app_color, $color_def);

		$this->db->query('add-app-color', [
			':app' => $app,
			':color_name' => $app_color,
			':sys_color' => $color->default()
		]);
	}

	public function removeAppColor(string $app, string $app_color): void
	{
		$this->db->query('remove-app-color', [
			':app' => $app,
			':color_name' => $app_color
		]);
	}

	public function appColorNames(string $app): array
	{
		$result = $this->db->query('app-color-names', $app);
		return $result->column('name');
	}

	public function getActiveThemes(): array
	{
		$dark = $this->db->queryValue('get-prop', 'active-dark-theme');
		$light = $this->db->queryValue('get-prop', 'active-light-theme');

		$out = [];
		if (!is_null($dark))
			$out['dark'] = intval($dark);

		if (!is_null($light))
			$out['light'] = intval($light);

		return $out;
	}

	public function activateTheme(string $type, int $theme_id): void
	{
		if (!($type === 'light' || $type === 'dark'))
			throw new \Exception("type not supported: $type");

		// avoid having dangling "other" theme (change light to dark)
		$this->deactivateTheme($theme_id);

		$this->db->query('set-prop', [
			':name' => "active-$type-theme",
			':value' => $theme_id
		]);
	}

	public function deactivateTheme(int $theme_id): void
	{
		$this->db->query('deactivate-theme', $theme_id);
	}

	// only names and ids - no deep loading
	public function availableThemes(): array
	{
		$result = $this->db->query('load-themes');
		return $result->indexById();
	}
}
