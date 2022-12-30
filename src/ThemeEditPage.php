<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

class ThemeEditPage extends Page
{
	const NAME_PATTERN =  "^[a-zA-Z0-9 ]+$";
	const HEX_PATTERN = '^#[0-9a-fA-F]{6}$';

	public function __construct(
		private ColorDatabase $db,
		private array $colors
	)
	{
	}

	public function title()
	{
		return 'Edit Theme';
	}

	public function stylesheets()
	{
		return ['/static/theme_page.css'];
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

	public static function enumerateShades(array $color): array
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

	public function body()
	{
		$THEME = null;
		$AVAILABLE_THEMES = $this->db->availableThemes();
		$AVAILABLE_PALETTES = $this->db->availablePalettes();
		$NAME_PATTERN = self::NAME_PATTERN;
		$THEME_COLOR_HEX = [];
		$STATUS = 'inactive';

		$action = strtolower($_REQUEST['action'] ?? 'select');

		if ($action === 'create')
		{
			$THEME = $this->db->createTheme();
		}
		else if ($action === 'select')
		{
		}
		else if ($action === 'add color')
		{
			// TODO: don't trash rest of draft just to add color
			$id = $this->parseId('theme-id');
			$this->db->createThemeColor($id);
			$THEME = $this->db->loadTheme($id);
		}
		else if ($action === 'change palette')
		{
			$id = $this->parseId('theme-id');
			$palette = $this->parseId('theme-palette');

			$this->db->changeThemePalette($id, $palette);

			$THEME = $this->db->loadTheme($id);
		}
		else if ($action === 'save')
		{
			$id = $this->parseId('theme-id');
			$name = $this->parseName('theme-name');
			$theme_colors = $this->parseThemeColors();
			$mappings = $this->parseColorMappings();
			$status = $this->parseStatus();

			$this->db->saveTheme([
				'id' => $id,
				'name' => $name,
				'theme-colors' => $theme_colors,
				'mappings' => $mappings
			]);

			if ($status === 'inactive')
			{
				$this->db->deactivateTheme($id);
			}
			else
			{
				$this->db->activateTheme($status, $id);
			}

			$THEME = $this->db->loadTheme($id);
		}
		else if ($action === 'edit')
		{
			$THEME = $this->db->loadTheme($this->parseId('theme-id'));
		}
		else
		{
			http_response_code(400);
			echo "Invalid action " . htmlspecialchars($action) . "\n";
			exit;
		}

		if (isset($THEME))
		{
			foreach ($THEME['theme-colors'] as $id => $theme_color)
			{
				$THEME_COLOR_HEX[$id] = [
					'bg' => '#ffffff',
					'fg' => '#000000'
				];

				if (empty($THEME['palette']))
				{
					continue;
				}

				$palette_colors = $THEME['palette']['colors_assoc'];

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

		require __DIR__ . '/../template/theme_page.php';
	}
}
