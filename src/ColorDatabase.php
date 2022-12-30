<?php

namespace Shell;

require __DIR__ . '/../vendor/autoload.php';

class ColorDatabase
{
	const LIGHTNESS_DENOM = 10000;
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

	public function initReentrant(): ?string
	{
		if ($this->db->queryValue('table-exists', 'props'))
		{
			$db_version = $this->db->queryValue('get-prop', 'version');
			$db_consumer = new Semver(0,1,0); // this is server

			if (!$db_version)
				return 'Expected a database that was set up to have a version listed in props';

			$db_semver = Semver::parse($db_version);

			// this means, "will all of the queries in my source branch work on the DB?"
			if (!$db_semver->canSupport($db_consumer))
				return "DB version ($db_semver) is incompatible with server software ($db_consumer)";

			return null; // this means the DB is set up and can support us
		}

		if (!$this->db->exec('init-color'))
			return 'failed to initialize database';

		return null;
	}

	public function availablePalettes(): array
	{
		$result = $this->db->query('get-palettes');
		if (!$result)
			throw new \Exception("Error reading available palettes");

		$rows = [];
		foreach ($result->rows() as $row)
			array_push($rows, $row);

		return $rows;
	}

	public function createPalette(string $name): array
	{
		if (!$this->db->query('create-palette', $name))
		{
			throw new \Exception('Error creating palette');
		}

		$rowid = $this->db->lastInsertRowID();
		$palette = $this->db->loadRowUnsafe('palette', $rowid);
		$palette['colors'] = [];
		return $palette;
	}

	public function loadPalette(int $id): array
	{
		$palette = $this->db->queryRow('load-palette', $id);

		if (!$palette)
			return null;

		$result = $this->db->query('load-palette-colors', $id);
		if (!$result)
			throw new \Exception('Failed to load colors');

		$colors = [];
		$colors_assoc = [];
		foreach ($result->rows() as $row)
		{
			array_push($colors, $row);
			$colors_assoc[$row['id']] = $row;
		}

		$palette['colors'] = $colors;
		$palette['colors_assoc'] = $colors_assoc; // TODO: make this default

		return $palette;
	}

	// create a new color in the palette and return the id of the color
	public function createPaletteColor(int $id): int
	{
		$this->db->query('create-palette-color', $id);

		$rowid = $this->db->lastInsertRowID();
		$color = $this->db->loadRowUnsafe('color', $rowid);

		return $color['id'];
	}

	public function getPaletteFromColor(int $color_id): int
	{
		return $this->db->queryValue('get-color-palette', $color_id);
	}

	public function savePalette(array $palette): bool
	{
		$result = $this->db->query('save-palette', [
			':id' => $palette['id'],
			':name' => $palette['name']
		]);

		if (!$result)
			return false;

		foreach ($palette['colors'] as $color)
		{
			$result = $this->db->query('save-palette-color', [
				':id' => $color['id'],
				':name' => $color['name'],
				':hex' => $color['hex']
			]);

			if (!$result)
				return false;
		}

		return true;
	}

	public function createTheme(): array
	{
		if (!$this->db->query('create-theme'))
		{
			throw new \Exception('Error creating theme');
		}

		$id = $this->db->lastInsertRowID();

		if (!$this->db->query('init-theme-color-map', $id))
		{
			throw new \Exception('Error creating theme color map');
		}

		return $this->loadTheme($id);
	}

	public function loadTheme(int $theme_id): array
	{
		$row = $this->db->loadRowUnsafe('theme', $theme_id);
		$theme = [
			'id' => $row['id'],
			'name' => $row['name'],
		];

		if (is_int($row['palette']))
			$theme['palette'] = $this->loadPalette($row['palette']);

		// load theme colors
		$result = $this->db->query('load-theme-colors', $theme_id);
		$theme['theme-colors'] = [];
		foreach ($result->rows() as $row)
		{
			$row['fg_lightness'] /= self::LIGHTNESS_DENOM;
			$row['bg_lightness'] /= self::LIGHTNESS_DENOM;
			$theme['theme-colors'][$row['id']] = $row;
		}

		// load color mappings
		$result = $this->db->query('load-theme-color-map', $theme_id);
		if (!$result)
			throw new \Exception('Failed to load theme color mappings');

		$theme['mappings'] = [];
		foreach ($result->rows() as $row)
			$theme['mappings'][$row['id']] = $row; 

		return $theme;
	}

	public function saveTheme(array $theme): void
	{
		$this->db->query('set-theme-name', [
			':id' => $theme['id'],
			':name' => $theme['name']
		]);

		foreach ($theme['theme-colors'] as $id => $theme_color)
		{
			$fg_lightness = floor($theme_color['fg_lightness'] * self::LIGHTNESS_DENOM);
			$bg_lightness = floor($theme_color['bg_lightness'] * self::LIGHTNESS_DENOM);

			$this->db->query('save-theme-color', [
				':theme' => $theme['id'],
				':id' => $id,
				':name' => $theme_color['name'],
				':bg_color' => $theme_color['bg_color'],
				':bg_lightness' => $bg_lightness,
				':fg_color' => $theme_color['fg_color'],
				':fg_lightness' => $fg_lightness
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
		if (!$this->db->query('clear-theme-color', $theme_id))
		{
			throw new \Exception('Failed to clear theme colors');
		}

		if (!$this->db->query('change-palette', [
			':theme' => $theme_id,
			':palette' => $palette_id
		]))
		{
			throw new \Exception('Failed to change palette');
		}
	}

	public function createThemeColor(int $theme_id): array
	{
		$this->db->query('create-theme-color', $theme_id);
		$id = $this->db->lastInsertRowID();
		return $this->db->loadRowUnsafe('theme_color', $id);
	}

	public function createColorMapping(int $theme_id, string $app, string $semantic_color): array
	{
		if (!$this->db->query('create-color-mapping', [
			':theme' => $theme_id,
			':app' => $app,
			':color_name' => $semantic_color
		]))
		{
			throw new \Exception('Failed to create color mapping');
		}

		return $this->db->loadRowUnsafe('theme_color', $this->db->lastInsertRowID());
	}

	public function addSemanticColor(string $app, string $semantic_color): void
	{
		$this->db->query('add-semantic-color', [
			':app' => $app,
			':color_name' => $semantic_color
		]);
	}

	public function removeSemanticColor(string $app, string $semantic_color): void
	{
		$this->db->query('remove-semantic-color', [
			':app' => $app,
			':color_name' => $semantic_color
		]);
	}

	// delete theme mappings to dangling semantic colors (probably removed on upgrade)
	// add empty theme mappings to existing themes with new semantic colors
	public function syncSemanticColors(): void
	{
		$this->db->exec('sync-semantic-colors');
	}

	public function semanticColorNames(string $app): array
	{
		$result = $this->db->query('semantic-color-names', $app);

		$rows = [];

		foreach ($result->rows() as $row)
			array_push($rows, $row['name']);

		return $rows;
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

		$out = [];
		foreach ($result->rows() as $row)
		{
			$out[$row['id']] = $row;
		}

		return $out;
	}
}
