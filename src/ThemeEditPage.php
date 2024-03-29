<?php

namespace Gulachek\Bassline;

function isSystemColor(array $theme_color): bool
{
	return !\is_null($theme_color['system_color']);
}

class ThemeEditPage extends Responder
{
	const NAME_PATTERN =  "^[a-zA-Z0-9\- ]+$";
	const HEX_PATTERN = '^#[0-9a-fA-F]{6}$';

	public function __construct(
		private ColorDatabase $db,
		private array $colors
	)
	{
	}

	private static function nameField(): InputField
	{
		return new InputField(
			title: 'Letters, numbers, and spaces',
			pattern: self::NAME_PATTERN
		);
	}

	private static function isName(?string $name): bool
	{
		return self::nameField()->validate($name);
	}

	private function parsePattern(string $name, string $pattern): ?string
	{
		if (empty($_REQUEST[$name]))
			return null;

		if (!preg_match("/^$pattern$/", $_REQUEST[$name]))
			return null;

		return $_REQUEST[$name];
	}

	private function parseId(string $name): ?int
	{
		$n = \intval($_REQUEST[$name] ?? -1);
		return $n < 1 ? null : $n;
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

		return $arg->renderPage(
			title: 'Edit Theme',
			template: __DIR__ . '/../template/theme_page.php',
			args: [
				'theme' => $THEME,
				'name_pattern' => $NAME_PATTERN,
				'available_palettes' => $AVAILABLE_PALETTES,
				'hex' => $THEME_COLOR_HEX,
				'available_themes' => $AVAILABLE_THEMES,
				'self' => $this
			]
		);
	}

	private static function systemUnavailable(): ErrorPage
	{
		\header('Retry-After: 5');
		return new ErrorPage(503, 'System Unavailable', 'The system is currently too busy to allow editing themes. Try again.');
	}

	private static function themeUnavailable(string $uname): ErrorPage
	{
		return new ErrorPage(
			errorCode: 409, 
			title: 'Theme Unavailable',
			msg: "This theme is being edited by '{$uname}'. Try again when the theme is no longer being edited."
		);
	}

	private function editTheme(RespondArg $arg): mixed
	{
		$theme = null;
		$available_palettes = $this->db->availablePalettes();
		$status = 'inactive';

		$id = $this->parseId('id');
		if (\is_null($id))
			return new ErrorPage(400, 'Bad Request', 'Invalid palette id');

		if (!$this->db->lock())
			return self::systemUnavailable();

		try
		{
			$theme = $this->db->loadTheme($id);

			if (!$theme)
				return self::themeNotFound();

			$token = $this->tryReserveTheme($arg->uid(), $theme);
			if (!$token)
			{
				$currentToken = SaveToken::decode($theme['save_token']);
				$uname = $arg->username($currentToken->userId);
				return self::themeUnavailable($uname);
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
				'nameField' => self::nameField(),
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

			if (!$currentTheme)
			{
				return new ThemeSaveResponse(404,
					"Theme '$id' not found");
			}

			if (!self::isName($theme->name))
			{
				return new ThemeSaveResponse(400,
					"Invalid theme name '{$theme->name}'");
			}

			$token = $this->tryReserveTheme($arg->uid(),
				$currentTheme, $theme->saveKey);

			if (!$token)
			{
				$currentToken = SaveToken::decode($currentTheme['save_token']);
				$uname = $arg->username($currentToken->userId);

				$msg = "This theme was edited by '{$uname}' and the information you're seeing might be inaccurate. You will not be able to continue editing until you reload the page.";

				return new ThemeSaveResponse(409, $msg);
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
					return new ThemeSaveResponse(400, ['error' => 'Theme color not part of theme']);
				}

				$theme_color = $currentTheme['themeColors'][$colorId];

				if (isSystemColor($theme_color))
				{
					return new ThemeSaveResponse(400, ['error' => 'Cannot delete system color from theme']);
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
				if (!\array_key_exists($colorId, $currentTheme['themeColors']))
				{
					return new ThemeSaveResponse(400, ['error' => 'Theme color not part of theme']);
				}

				$current_color = $currentTheme['themeColors'][$colorId];
				if (isSystemColor($current_color)
					&& ($current_color['name'] !== $color->name))
				{
					return new ThemeSaveResponse(400, ['error' => 'System color names are constant']);
				}

				if (!self::isName($color->name))
				{
					return new ThemeSaveResponse(400, ['error' => 'Theme color name is invalid']);
				}

				if (!\array_key_exists($color->palette_color, $currentTheme['palette']['colors']))
				{
					return new ThemeSaveResponse(400, ['error' => 'Palette color does not belong to palette']);
				}

				if ($color->lightness < 0 || $color->lightness > 1)
				{
					return new ThemeSaveResponse(400, ['error' => 'Palette color lightness is not in range (0-1)']);
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
				if (!\array_key_exists($mappingId, $currentTheme['mappings']))
				{
					return new ThemeSaveResponse(400, ['error' => 'Mapping not part of theme']);
				}

				if (!\array_key_exists($mapping->theme_color, $currentTheme['themeColors']))
				{
					return new ThemeSaveResponse(400, ['error' => 'Mapping theme color not part of theme']);
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

			if ($req->status === 'light' || $req->status === 'dark')
			{
				$this->db->activateTheme($req->status, $id);
			}
			else if ($req->status === 'inactive')
			{
				$this->db->deactivateTheme($id);
			}
			else
			{
				return new ThemeSaveResponse(400, ['error' => 'Invalid theme status']);
			}

			return new ThemeSaveResponse(200, [
				'mappedColors' => $mappedColors,
				'newSaveKey' => $token->key
			]);
		}
		finally
		{
			$this->db->unlock();
		}
	}

	private static function themeNotFound(): ErrorPage
	{
		return new ErrorPage(404, 'Theme not found', "The requested theme doesn't exist");
	}

	private function changePalette(RespondArg $arg): mixed
	{
		$id = $this->parseId('theme-id');
		$palette = $this->parseId('palette-id');

		if (!($id && $palette))
			return new ErrorPage(400, 'Bad Request', 'Invalid theme or palette id');

		if (!$this->db->lock())
			return self::systemUnavailable();

		try
		{
			$theme = $this->db->loadTheme($id);

			if (!$theme)
				return self::themeNotFound();

			$token = $this->tryReserveTheme($arg->uid(), $theme);
			if (!$token)
			{
				$currentToken = SaveToken::decode($theme['save_token']);
				$uname = $arg->username($currentToken->userId);

				return self::themeUnavailable($uname);
			}

			$theme['save_token'] = $token->encode();
			$this->db->saveTheme($theme);

			$this->db->changeThemePalette($id, $palette);

			return new Redirect("/site/admin/theme/edit?id=$id");
		}
		finally
		{
			$this->db->unlock();
		}
	}

	private function tryReserveTheme(int $uid, array $theme, ?string $key = null): ?SaveToken
	{
		return SaveToken::tryReserveEncoded($uid, $theme['save_token'], $key);
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

class ThemeSaveResponse extends Responder
{
	public function __construct(
		public int $code,
		public string|array $errorOrBody,
	)
	{ }

	public function respond(RespondArg $arg): mixed
	{
		\http_response_code($this->code);
		\header('Content-Type: application/json');
		if (\is_array($this->errorOrBody))
			echo \json_encode($this->errorOrBody);
		else
			echo \json_encode(['error' => $this->errorOrBody]);

		return null;
	}
}
