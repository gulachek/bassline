<?php

namespace Gulachek\Bassline;

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

	private function parseId(string $name): int
	{
		return intval($this->parsePattern($name, '\d+'));
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
			return null;
		}
	}

	private function createTheme(RespondArg $arg): mixed
	{
		$theme = $this->db->createTheme(isDark: false);
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
		$STATUS = 'inactive';

		$THEME = $this->db->loadTheme($this->parseId('id'));

		if (!$THEME)
		{
			\http_response_code(404);
			echo "Theme not found";
			return null;
		}

		$active_themes = $this->db->getActiveThemes();
		foreach ($active_themes as $type => $id)
		{
			if ($id === $THEME['id'])
				$STATUS = $type;
		}

		$model = [
			'theme' => $THEME,
			'name_pattern' => $NAME_PATTERN,
			'available_palettes' => $AVAILABLE_PALETTES,
			'status' => $STATUS,
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
				'color' => $color->color,
				'lightness' => $color->lightness,
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
	public ?int $color;
	public float $lightness;
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
