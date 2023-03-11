<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

class ThemeEditPage extends Responder
{
	const NAME_PATTERN =  "^[a-zA-Z0-9 ]+$";
	const HEX_PATTERN = '^#[0-9a-fA-F]{6}$';

	public function __construct(
		private ColorDatabase $db,
		private array $colors
	)
	{
	}

	private function parsePattern(string $name, string $pattern): string
	{
		if (empty($_REQUEST[$name]))
		{
			http_response_code(400);
			echo "No $name\n";
			exit;
		}

		if (!preg_match("/^$pattern$/", $_REQUEST[$name]))
		{
			http_response_code(400);
			echo "Invalid $name\n";
			exit;
		}

		return $_REQUEST[$name];
	}

	private function parsePatternArray(string $name, string $pattern): array
	{
		if (!is_array($_REQUEST[$name]))
		{
			http_response_code(400);
			echo "No array $name\n";
			exit;
		}

		foreach ($_REQUEST[$name] as $elem)
		{
			if (!preg_match("/^$pattern$/", $elem))
			{
				http_response_code(400);
				echo "Invalid $name\n";
				exit;
			}
		}

		return $_REQUEST[$name];
	}

	private function parseId(string $name): int
	{
		return intval($this->parsePattern($name, '\d+'));
	}

	private function parseIdArray(string $name): array
	{
		$ids = [];

		foreach ($this->parsePatternArray($name, '\d+') as $elem)
		{
			array_push($ids, intval($elem));
		}

		return $ids;
	}

	private function parseNullableIdArray(string $name): array
	{
		$ids = [];

		foreach ($this->parsePatternArray($name, '\d*') as $elem)
		{
			array_push($ids, strlen($elem) ? intval($elem) : null);
		}

		return $ids;
	}

	private function parseDouble(string $name): float
	{
		return doubleval($this->parsePattern($name, '-?[0-9]*(\.[0-9]+)?'));
	}

	private function parseDoubleArray(string $name): array
	{
		$dbls = [];

		foreach ($this->parsePatternArray($name, '-?[0-9]*(\.[0-9]+)?') as $elem)
		{
			array_push($dbls, doubleval($elem));
		}

		return $dbls;
	}

	private function parseName(string $name): string
	{
		return $this->parsePattern($name, self::NAME_PATTERN);
	}

	private function parseNameArray(string $name): array
	{
		return $this->parsePatternArray($name, self::NAME_PATTERN);
	}

	private function parseLightnessArray($name): array
	{
		$lightnesses = [];

		foreach ($this->parseDoubleArray($name) as $elem)
		{
			if ($elem < 0 || $elem > 1)
			{
				http_response_code(400);
				echo "Invalid lightness\n";
				exit;
			}

			array_push($lightnesses, $elem);
		}

		return $lightnesses;
	}

	private function parseStatus(): string
	{
		return $this->parsePattern('theme-status', '(inactive|dark|light)');
	}

	private function parseThemeColors(): array
	{
		if (empty($_REQUEST['theme-color-ids']))
		{
			return [];
		}

		$ids = $this->parseIdArray('theme-color-ids');
		$names = $this->parseNameArray('theme-color-names');
		$bg_ids = $this->parseNullableIdArray('theme-color-bg-colors');
		$bg_light = $this->parseLightnessArray('theme-color-bg-lightnesses');
		$fg_ids = $this->parseNullableIdArray('theme-color-fg-colors');
		$fg_light = $this->parseLightnessArray('theme-color-fg-lightnesses');

		$n = count($ids);
		if (count($names) !== $n
			|| count($bg_ids) !== $n
			|| count($bg_light) !== $n
			|| count($fg_ids) !== $n
			|| count($fg_light) !== $n
		)
		{
			http_response_code(400);
			echo "Invalid theme color array sizes\n";
			exit;
		}

		$colors = [];
		for ($i = 0; $i < $n; ++$i)
		{
			$colors[$ids[$i]] = [
				'id' => $ids[$i],
				'name' => $names[$i],
				'bg_color' => $bg_ids[$i],
				'bg_lightness' => $bg_light[$i],
				'fg_color' => $fg_ids[$i],
				'fg_lightness' => $fg_light[$i]
			];
		}

		return $colors;
	}

	private function parseColorMappings(): array
	{
		if (!isset($_REQUEST['mapping-ids']))
			return [];

		// can happen if no theme colors are defined yet
		if (!isset($_REQUEST['mapping-theme-colors']))
			return [];

		$ids = $this->parseIdArray('mapping-ids');
		$theme_colors = $this->parseNullableIdArray('mapping-theme-colors');

		$n = count($ids);
		if (count($theme_colors) !== $n)
		{
			http_response_code(400);
			echo "Invalid mapping array sizes\n";
			exit;
		}

		$mappings = [];
		for ($i = 0; $i < $n; ++$i)
		{
			$mappings[$ids[$i]] = [
				'id' => $ids[$i],
				'theme_color' => $theme_colors[$i]
			];
		}

		return $mappings;
	}

	public function enumerateShades(array $color): array
	{
		$shades = [];
		$srgb = SRGB::fromHex($color['hex']);

		list($h,$s,$l) = $srgb->toHSL();

		$n = ColorDatabase::SHADE_COUNT;
		$min = 0.025;
		$max = 0.975;
		$del = ($max - $min) / ($n-1);

		for ($i = 0; $i < $n; ++$i)
		{
			array_push($shades, SRGB::fromHSL([$h, $s, $i*$del + $min]));
		}

		return $shades;
	}

	public function respond(RespondArg $arg): mixed
	{
		if (!$arg->userCan('edit_themes'))
		{
			http_response_code(401);
			echo "Not authorized";
			return null;
		}

		$path = $arg->path;

		if ($path->count() > 1)
			return new NotFound();

		$THEME = null;
		$AVAILABLE_THEMES = $this->db->availableThemes();
		$AVAILABLE_PALETTES = $this->db->availablePalettes();
		$NAME_PATTERN = self::NAME_PATTERN;
		$THEME_COLOR_HEX = [];
		$STATUS = 'inactive';

		$action = strtolower($_REQUEST['action'] ?? '');
		if (!$action)
			$action = $path->isRoot() ? 'select' : $path->at(0);

		if ($action === 'create')
		{
			return $this->createTheme($arg);
		}
		else if ($action === 'select')
		{
			return $this->selectTheme($arg);
		}
		else if ($action === 'add color')
		{
			// TODO: don't trash rest of draft just to add color
			$id = $this->parseId('theme-id');
			$this->db->createThemeColor($id);
			$THEME = $this->db->loadTheme($id);
		}
		else if ($action === 'change_palette')
		{
			return $this->changePalette($arg);
		}
		else if ($action === 'save')
		{
			return $this->saveTheme($arg);
		}
		else if ($action === 'edit')
		{
			return $this->editTheme($arg);
		}
		else
		{
			http_response_code(400);
			echo "Invalid action " . htmlspecialchars($action) . "\n";
			exit;
		}

		if (isset($THEME))
		{
			foreach ($THEME['themeColors'] as $id => $theme_color)
			{
				$THEME_COLOR_HEX[$id] = [
					'bg' => '#ffffff',
					'fg' => '#000000'
				];

				if (empty($THEME['palette']))
				{
					continue;
				}

				$palette_colors = $THEME['palette']['colors'];

				if (isset($theme_color['bg_color']))
				{
					$bg = SRGB::fromHex($palette_colors[$theme_color['bg_color']]['hex']);
					list($h, $s, $l) = $bg->toHSL();
					$l = $theme_color['bg_lightness'];
					$THEME_COLOR_HEX[$id]['bg'] = SRGB::fromHSL([$h,$s,$l])->toHex();
				}

				if (isset($theme_color['fg_color']))
				{
					$fg = SRGB::fromHex($palette_colors[$theme_color['fg_color']]['hex']);
					list($h, $s, $l) = $fg->toHSL();
					$l = $theme_color['fg_lightness'];
					$THEME_COLOR_HEX[$id]['fg'] = SRGB::fromHSL([$h,$s,$l])->toHex();
				}
			}

			$active_themes = $this->db->getActiveThemes();
			foreach ($active_themes as $type => $id)
			{
				if ($id === $THEME['id'])
					$STATUS = $type;
			}
		}

		$arg->renderPage([
			'title' => 'Edit Theme',
			'template' => __DIR__ . '/../template/theme_page.php',
			'args' => [
				'theme' => $THEME,
				'name_pattern' => $NAME_PATTERN,
				'available_palettes' => $AVAILABLE_PALETTES,
				'status' => $STATUS,
				'hex' => $THEME_COLOR_HEX,
				'available_themes' => $AVAILABLE_THEMES,
				'self' => $this
			]
		]);

		return null;
	}

	private function createTheme(RespondArg $arg): mixed
	{
		$theme = $this->db->createTheme();
		$id = $theme['id'];
		return new Redirect("/site/admin/theme/edit?id=$id");
	}

	private function selectTheme(RespondArg $arg): mixed
	{
		$THEME = null;
		$AVAILABLE_THEMES = $this->db->availableThemes();
		$AVAILABLE_PALETTES = $this->db->availablePalettes();
		$NAME_PATTERN = self::NAME_PATTERN;
		$THEME_COLOR_HEX = [];

		$arg->renderPage([
			'title' => 'Edit Theme',
			'template' => __DIR__ . '/../template/theme_page.php',
			'args' => [
				'theme' => $THEME,
				'name_pattern' => $NAME_PATTERN,
				'available_palettes' => $AVAILABLE_PALETTES,
				'hex' => $THEME_COLOR_HEX,
				'available_themes' => $AVAILABLE_THEMES,
				'self' => $this
			]
		]);

		return null;
	}

	private function editTheme(RespondArg $arg): mixed
	{
		$THEME = null;
		$AVAILABLE_THEMES = $this->db->availableThemes();
		$AVAILABLE_PALETTES = $this->db->availablePalettes();
		$NAME_PATTERN = self::NAME_PATTERN;
		$THEME_COLOR_HEX = [];
		$STATUS = 'inactive';

		$THEME = $this->db->loadTheme($this->parseId('id'));

		if (isset($THEME))
		{
			foreach ($THEME['themeColors'] as $id => $theme_color)
			{
				$THEME_COLOR_HEX[$id] = [
					'bg' => '#ffffff',
					'fg' => '#000000'
				];

				if (empty($THEME['palette']))
				{
					continue;
				}

				$palette_colors = $THEME['palette']['colors'];

				if (isset($theme_color['bg_color']))
				{
					$bg = SRGB::fromHex($palette_colors[$theme_color['bg_color']]['hex']);
					list($h, $s, $l) = $bg->toHSL();
					$l = $theme_color['bg_lightness'];
					$THEME_COLOR_HEX[$id]['bg'] = SRGB::fromHSL([$h,$s,$l])->toHex();
				}

				if (isset($theme_color['fg_color']))
				{
					$fg = SRGB::fromHex($palette_colors[$theme_color['fg_color']]['hex']);
					list($h, $s, $l) = $fg->toHSL();
					$l = $theme_color['fg_lightness'];
					$THEME_COLOR_HEX[$id]['fg'] = SRGB::fromHSL([$h,$s,$l])->toHex();
				}
			}

			$active_themes = $this->db->getActiveThemes();
			foreach ($active_themes as $type => $id)
			{
				if ($id === $THEME['id'])
					$STATUS = $type;
			}
		}

		$model = [
			'theme' => $THEME,
			'name_pattern' => $NAME_PATTERN,
			'available_palettes' => $AVAILABLE_PALETTES,
			'status' => $STATUS,
			'hex' => $THEME_COLOR_HEX,
			'semantic_colors' => $this->colors
		];

		ReactPage::render($arg, [
			'title' => "Edit {$THEME['name']}",
			'scripts' => ['/assets/themeEdit.js'],
			'model' => $model
		]);
		return null;
	}

	private function saveTheme(RespondArg $arg): mixed
	{
		// TODO: scrutinize client input
		$req = $arg->parseBody(ThemeSaveRequest::class);
		if (!$req)
		{
			http_response_code(400);
			echo json_encode(['error' => 'Bad request encoding']);
			return null;
		}

		$theme = $req->theme;
		$id = $theme->id;

		$currentTheme = $this->db->loadTheme($id);

		$themeToSave = [
			'id' => $id,
			'name' => $theme->name,
			'themeColors' => [],
			'mappings' => []
		];

		foreach ($theme->themeColors->deletedItems as $colorId)
		{
			if (!array_key_exists($colorId, $currentTheme['themeColors']))
			{
				http_response_code(400);
				echo json_encode(['error' => 'Theme color not part of theme']);
				return null;
			}

			$this->db->deleteThemeColor($colorId);
		}

		$mappedColors = [];
		foreach ($theme->themeColors->newItems as $tempId => $color)
		{
			$newColor = $this->db->createThemeColor($id);
			$mappedColors[$tempId] = $newColor['id'];
			$currentTheme['themeColors'][$newColor['id']] = $newColor;
			$theme->themeColors->items[$newColor['id']] = $color;
		}

		foreach ($theme->themeColors->items as $colorId => $color)
		{
			if (!array_key_exists($colorId, $currentTheme['themeColors']))
			{
				http_response_code(400);
				echo json_encode(['error' => 'Theme color not part of theme']);
				return null;
			}

			$themeToSave['themeColors'][$colorId] = [
				'id' => $colorId,
				'name' => $color->name,
				'fg_color' => $color->fg_color,
				'fg_lightness' => $color->fg_lightness,
				'bg_color' => $color->bg_color,
				'bg_lightness' => $color->bg_lightness
			];
		}

		foreach ($theme->mappings as $mappingId => $mapping)
		{
			if (!array_key_exists($mappingId, $currentTheme['mappings']))
			{
				http_response_code(400);
				echo json_encode(['error' => 'Mapping not part of theme']);
				return null;
			}

			if (!array_key_exists($mapping->theme_color, $currentTheme['themeColors']))
			{
				http_response_code(400);
				echo json_encode(['error' => 'Mapping theme color not part of theme']);
				return null;
			}

			$currentMapping = $currentTheme['mappings'][$mappingId];

			$themeToSave['mappings'][$mappingId] = [
				'id' => $mappingId,
				'app' => $currentMapping['app'],
				'name' => $currentMapping['name'],
				'theme_color' => $mapping->theme_color
			];
		}

		$this->db->saveTheme($themeToSave);

		if ($req->status === 'inactive')
		{
			$this->db->deactivateTheme($id);
		}
		else
		{
			$this->db->activateTheme($req->status, $id);
		}

		echo json_encode(['mappedColors' => $mappedColors]);
		return null;
	}

	private function changePalette(RespondArg $arg): mixed
	{
		$id = $this->parseId('theme-id');
		$palette = $this->parseId('palette-id');

		$this->db->changeThemePalette($id, $palette);

		return new Redirect("/site/admin/theme/edit?id=$id");
	}
}

class ThemeColor
{
	public int $id;
	public string $name;
	public ?int $bg_color;
	public float $bg_lightness;
	public ?int $fg_color;
	public float $fg_lightness;
}

class ThemeMapping
{
	public int $id;
	public string $app;
	public string $name;
	public int $theme_color;
}

class EditableThemeColorMap
{
	#[AssocProperty('int', ThemeColor::class)]
	public array $items;

	#[AssocProperty('string', ThemeColor::class)]
	public array $newItems;

	#[ArrayProperty('string')]
	public array $deletedItems;
}

class EditedTheme
{
	public int $id;
	public string $name;
	public EditableThemeColorMap $themeColors;

	#[AssocProperty('int', ThemeMapping::class)]
	public array $mappings;
}

class ThemeSaveRequest
{
	public EditedTheme $theme;
	public string $status;
}
