<?php

namespace Gulachek\Bassline;

function isSystemColor(array $theme_color): bool
{
	return !\is_null($theme_color['system_color']);
}

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
		$theme = null;
		$available_palettes = $this->db->availablePalettes();
		$status = 'inactive';

		$id = $this->parseId('id');

		if (!$this->db->lock())
		{
			\http_response_code(503);
			\header('Retry-After: 5');
			echo "Database is unavailable. Try again.";
			return null;
		}

		try
		{
			$theme = $this->db->loadTheme($id);

			if (!$theme)
			{
				\http_response_code(404);
				echo "Theme not found";
				return null;
			}

			$token = $this->tryReserveTheme($arg->uid(), $theme);
			if (!$token)
			{
				$currentToken = SaveToken::decode($theme['save_token']);
				$uname = $arg->username($currentToken->userId);

				\http_response_code(409);
				echo "This theme is being edited by '{$uname}'. Try again when the theme is no longer being edited.";
				return null;
			}

			$theme['save_token'] = $token->encode();
			$this->db->saveTheme($theme);

			$active_themes = $this->db->getActiveThemes();
			foreach ($active_themes as $type => $activeId)
			{
				if ($activeId === $id)
					$status = $type;
			}

			$model = [
				'theme' => $theme,
				'name_pattern' => self::NAME_PATTERN,
				'available_palettes' => $available_palettes,
				'status' => $status,
				'app_colors' => $this->colors,
				'initialSaveKey' => $token->key
			];

			ReactPage::render($arg, [
				'title' => "Edit {$theme['name']}",
				'scripts' => ['/assets/themeEdit.js'],
				'model' => $model
			]);
			return null;
		}
		finally
		{
			$this->db->unlock();
		}
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

		if (!$this->db->lock())
		{
			\http_response_code(503);
			\header('Retry-After: 5');
			echo \json_encode(['error' => 'Database unavailable']);
			return null;
		}

		try
		{
			$theme = $req->theme;
			$id = $theme->id;

			$currentTheme = $this->db->loadTheme($id);

			$token = $this->tryReserveTheme($arg->uid(),
				$currentTheme, $theme->saveKey);

			if (!$token)
			{
				$currentToken = SaveToken::decode($currentTheme['save_token']);
				$uname = $arg->username($currentToken->userId);

				$msg = "This theme was edited by '{$uname}' and the information you're seeing might be inaccurate. You will not be able to continue editing until you reload the page.";

				\http_response_code(409);
				echo \json_encode(['error' => $msg]);
				return null;
			}

			$themeToSave = [
				'id' => $id,
				'name' => $theme->name,
				'save_token' => $token->encode(),
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

				$theme_color = $currentTheme['themeColors'][$colorId];

				if (isSystemColor($theme_color))
				{
					http_response_code(400);
					echo json_encode(['error' => 'Cannot delete system color from theme']);
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

				$current_color = $currentTheme['themeColors'][$colorId];
				if (isSystemColor($current_color)
					&& ($current_color['name'] !== $color->name))
				{
					http_response_code(400);
					echo json_encode(['error' => 'System color names are constant']);
					return null;
				}

				$themeToSave['themeColors'][$colorId] = [
					'id' => $colorId,
					'name' => $color->name,
					'palette_color' => $color->palette_color,
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

			echo \json_encode([
				'mappedColors' => $mappedColors,
				'newSaveKey' => $token->key
			]);
			return null;
		}
		finally
		{
			$this->db->unlock();
		}
	}

	private function changePalette(RespondArg $arg): mixed
	{
		$id = $this->parseId('theme-id');
		$palette = $this->parseId('palette-id');

		$this->db->changeThemePalette($id, $palette);

		return new Redirect("/site/admin/theme/edit?id=$id");
	}

	private function tryReserveTheme(int $uid, array $theme, ?string $key = null): ?SaveToken
	{
		if ($theme['save_token'])
		{
			$token = SaveToken::decode($theme['save_token']);
			return $token->tryReserve($uid, $key);
		}
		else
		{
			return SaveToken::createForUser($uid);
		}
	}

}

class ThemeColor
{
	public int $id;
	public string $name;
	public ?int $palette_color;
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
	public string $saveKey;
	public EditableThemeColorMap $themeColors;

	#[AssocProperty('int', ThemeMapping::class)]
	public array $mappings;
}

class ThemeSaveRequest
{
	public EditedTheme $theme;
	public string $status;
}
