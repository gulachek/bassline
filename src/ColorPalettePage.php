<?php

namespace Gulachek\Bassline;

class ColorPalettePage extends Responder
{
	const NAME_PATTERN =  "^[a-zA-Z0-9 ]+$";
	const HEX_PATTERN = '^#[0-9a-fA-F]{6}$';

	public function __construct(
		private ColorDatabase $db
	)
	{
	}

	private function isNameValid(string $name): bool
	{
		$pattern = self::NAME_PATTERN;
		return preg_match("/$pattern/", $name);
	}

	public function respond(RespondArg $arg): mixed
	{
		if (!$arg->userCan('edit_themes'))
		{
			return new ErrorPage(401, 'Not Authorized', "In order to edit color palettes, you must log in with a user who can edit themes.");
		}

		$path = $arg->path;

		if ($path->count() > 1)
			return new NotFound();

		$action = $path->isRoot() ? 'select' : $path->at(0);

		if ($action === 'select')
		{
			return $this->select($arg);
		}
		else if ($action === 'create')
		{
			return $this->create($arg);
		}
		else if ($action === 'edit')
		{
			return $this->edit($arg);
		}
		else if ($action === 'save')
		{
			return $this->save($arg);
		}

		return null;
	}

	private function tryReservePalette(int $uid, array $palette, ?string $key = null): ?SaveToken
	{
		if ($palette['save_token'])
		{
			$token = SaveToken::decode($palette['save_token']);
			return $token->tryReserve($uid, $key);
		}
		else
		{
			return SaveToken::createForUser($uid);
		}
	}

	private function save(RespondArg $arg): mixed
	{
		$db = $this->db;

		$palette = $arg->parseBody(ColorPaletteSaveRequest::class);
		if (!$palette)
		{
			\http_response_code(400);
			echo \json_encode(['error' => 'Bad palette encoding']);
			return null;
		}

		$pattern = self::NAME_PATTERN;
		if (!preg_match("/$pattern/", $palette->name))
		{
			\http_response_code(400);
			echo \json_encode(['error' => 'Invalid name format']);
			return null;
		}

		if (!$db->lock())
		{
			\http_response_code(503);
			\header('Retry-After: 5');
			echo \json_encode(['error' => 'Database unavailable']);
			return null;
		}

		try
		{
			$currentPalette = $db->loadPalette($palette->id);
			if (!$currentPalette)
			{
				\http_response_code(404);
				echo \json_encode(['error' => 'Palette not found']);
				return null;
			}

			$token = $this->tryReservePalette($arg->uid(),
				$currentPalette, $palette->saveKey);

			if (!$token)
			{
				$currentToken = SaveToken::decode($currentPalette['save_token']);
				$uname = $arg->username($currentToken->userId);

				$msg = "This palette was edited by '{$uname}' and the information you're seeing might be inaccurate. You will not be able to continue editing until you reload the page.";

				\http_response_code(409);
				echo \json_encode(['error' => $msg]);
				return null;
			}

			$paletteToSave = [
				'id' => $palette->id,
				'name' => $palette->name,
				'save_token' => $token->encode(),
				'colors' => []
			];

			$mappedColors = [];

			foreach ($palette->colors->newItems as $tempId => $color)
			{
				$colorId = $db->createPaletteColor($palette->id);
				$color->id = $colorId;
				$mappedColors[$tempId] = $colorId;
				$palette->colors->items[$colorId] = $color;
			}

			foreach ($palette->colors->deletedItems as $id)
			{
				$db->deletePaletteColor($id);
			}

			foreach ($palette->colors->items as $id => $color)
			{
				$paletteToSave['colors'][$id] = [
					'id' => $id,
					'name' => $color->name,
					'hex' => $color->hex
				];
			}

			if ($db->savePalette($paletteToSave))
			{
				echo \json_encode([
					'mappedColors' => $mappedColors,
					'newSaveKey' => $token->key
				]);
			}
			else
			{
				\http_response_code(400);
				echo \json_encode(['error' => 'Failed to save palette']);
			}

			return null;
		}
		finally
		{
			$db->unlock();
		}
	}

	private function edit(RespondArg $arg): mixed
	{
		$id = intval($_REQUEST['id']);

		if (!$this->db->lock())
		{
			\http_response_code(503);
			\header('Retry-After: 5');
			echo "Database is busy. Try again in a few seconds.";
			return null;
		}

		try
		{
			$palette = $this->db->loadPalette($id);
			if (!$palette)
				return new NotFound();

			$token = $this->tryReservePalette($arg->uid(), $palette);
			if (!$token)
			{
				$currentToken = SaveToken::decode($palette['save_token']);
				$uname = $arg->username($currentToken->userId);

				return new ErrorPage(409, 'Palette Unavailable',
					"This palette is being edited by '{$uname}'. Try again when the palette is no longer being edited.");
			}

			$palette['save_token'] = $token->encode();
			$this->db->savePalette($palette);

			$model = [
				'palette' => $palette,
				'initialSaveKey' => $token->key
			];

			ReactPage::render($arg, [
				'title' => "Edit {$palette['name']}",
				'scripts' => ['/assets/colorPaletteEdit.js'],
				'model' => $model
			]);
			return null;
		}
		finally
		{
			$this->db->unlock();
		}
	}

	private function select(RespondArg $arg): mixed
	{
		$arg->renderPage([
			'title' => 'Select Color Palette',
			'template' => __DIR__ . '/../template/color_palette_select.php',
			'args' => [
				'name_pattern' => self::NAME_PATTERN,
				'available_palettes' => $this->db->availablePalettes()
			]
		]);

		return null;
	}

	private function create(RespondArg $arg): mixed
	{
		$name = $_POST['palette-name'];

		if (!$this->isNameValid($name))
		{
			return new ErrorPage(400, 'Invalid Palette Name',
				"The given palette name is invalid: '$name'");
		}

		$PALETTE = $this->db->createPalette($name);
		$id = $PALETTE['id'];
		return new Redirect("/site/admin/color_palette/edit?id=$id");
	}
}

class PaletteColor
{
	public int $id;
	public string $name;
	public string $hex;
}

class EditablePaletteColorMap
{
	#[AssocProperty('int', PaletteColor::class)]
	public array $items;

	#[AssocProperty('string', PaletteColor::class)]
	public array $newItems;

	#[ArrayProperty('string')]
	public array $deletedItems;
}

class ColorPaletteSaveRequest
{
	public int $id;
	public string $name;
	public EditablePaletteColorMap $colors;
	public string $saveKey;
}
