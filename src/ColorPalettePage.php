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

	private function postPaletteId()
	{
		if (empty($_POST['palette-id']))
		{
			http_response_code(400);
			echo "No palette\n";
			exit;
		}

		return intval($_POST['palette-id']);
	}

	private function isNameValid(string $name): bool
	{
		$pattern = self::NAME_PATTERN;
		return preg_match("/$pattern/", $name);
	}

	private function isColorValid(string $hex): bool
	{
		$pattern = self::HEX_PATTERN;
		return preg_match("/$pattern/", $hex);
	}

	private function postPaletteName()
	{
		$name = $_POST['palette-name'];

		if (!$this->isNameValid($name))
		{
			http_response_code(400);
			echo "Invalid name\n";
			exit;
		}

		return $name;
	}

	private function postColors($palette_id)
	{
		$colors = [];

		if (empty($_POST['color-ids']))
			return $colors;

		$arrays = ['ids', 'names', 'values'];
		$len = count($_POST['color-ids']);

		foreach ($arrays as $array)
		{
			if (!is_array($_POST["color-$array"]))
			{
				http_response_code(400);
				echo "Invalid color-$array\n";
				exit;
			}

			if (count($_POST["color-$array"]) !== $len)
			{
				http_response_code(400);
				echo "Invalid size for color-$array\n";
				exit;
			}
		}

		for ($i = 0; $i < $len; ++$i)
		{
			$id = intval($_POST['color-ids'][$i]);

			$color_palette_id = $this->db->getPaletteFromColor($id);
			if ($color_palette_id !== $palette_id)
			{
				http_response_code(400);
				echo "Color not in palette\n";
				exit;
			}

			$name = $_POST['color-names'][$i];

			if (!$this->isNameValid($name))
			{
				http_response_code(400);
				echo "Invalid color name\n";
				exit;
			}

			$hex = strtolower($_POST['color-values'][$i]);

			if (!$this->isColorValid($hex))
			{
				http_response_code(400);
				echo "Invalid color\n";
				exit;
			}

			$colors[$id] = [
				'id' => $id,
				'name' => $name,
				'hex' => $hex
			];
		}

		return $colors;
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

	private function save(RespondArg $arg): mixed
	{
		$db = $this->db;

		$palette = $arg->parseBody(ColorPaletteSaveRequest::class);
		if (!$palette)
		{
			http_response_code(400);
			echo json_encode(['error' => 'Bad palette encoding']);
			return null;
		}

		$pattern = self::NAME_PATTERN;
		if (!preg_match("/$pattern/", $palette->name))
		{
			http_response_code(400);
			echo json_encode(['error' => 'Invalid name format']);
			return null;
		}

		$paletteToSave = [
			'id' => $palette->id,
			'name' => $palette->name,
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
			echo json_encode(['mappedColors' => $mappedColors]);
		}
		else
		{
			http_response_code(400);
			echo json_encode(['error' => 'Failed to save palette']);
		}

		return null;
	}

	private function edit(RespondArg $arg): mixed
	{
		$id = intval($_REQUEST['id']);
		$palette = $this->db->loadPalette($id);
		if (!$palette)
			return new NotFound();

		$model = [
			'palette' => $palette,
		];

		ReactPage::render($arg, [
			'title' => "Edit {$palette['name']}",
			'scripts' => ['/assets/colorPaletteEdit.js'],
			'model' => $model
		]);
		return null;
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
		$name = $this->postPaletteName();
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
}
