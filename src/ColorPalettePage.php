<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

class ColorPalettePage extends Page
{
	private ColorDatabase $db;
	const NAME_PATTERN =  "^[a-zA-Z0-9 ]+$";
	const HEX_PATTERN = '^#[0-9a-fA-F]{6}$';

	public function __construct(
		private Config $config
	)
	{
		$this->db = ColorDatabase::fromConfig($config);
	}

	public function title()
	{
		return 'Edit Color Palette';
	}

	public function stylesheets()
	{
		return ['/static/color_palette_page.css'];
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

	public function body()
	{
		$AVAILABLE_PALETTES = [];
		$PALETTE = null;
		$NAME_PATTERN = self::NAME_PATTERN;
		$SHADE_COUNT = ColorDatabase::SHADE_COUNT;

		$action = strtolower($_POST['action'] ?? 'select');

		if ($action === 'select')
		{
			$AVAILABLE_PALETTES = $this->db->availablePalettes();
		}
		else if ($action === 'edit')
		{
			$id = $this->postPaletteId();
			$PALETTE = $this->db->loadPalette($id);
		}
		else if ($action === 'create')
		{
			$name = $this->postPaletteName();
			$PALETTE = $this->db->createPalette($name);
		}
		else if ($action === 'new color')
		{
			$id = $this->postPaletteId();
			$name = $this->postPaletteName();
			$colors = $this->postColors($id);

			$color_id = $this->db->createPaletteColor($id);

			$colors[$color_id] = [
				'id' => $color_id,
				'name' => 'New Color',
				'hex' => '#000000'
			];

			$PALETTE = [
				'id' => $id,
				'name' => $name,
				'colors' => $colors
			];
		}
		else if ($action === 'save')
		{
			$id = $this->postPaletteId();
			$name = $this->postPaletteName();
			$colors = $this->postColors($id);

			$PALETTE = [
				'id' => $id,
				'name' => $name,
				'colors' => $colors
			];

			if (!$this->db->savePalette($PALETTE))
			{
				http_response_code(500);
				echo "Failed to save palette\n";
				exit;
			}
		}
		else
		{
			http_response_code(400);
			echo "Invalid action\n";
			exit;
		}

		require __DIR__ . '/../template/color_palette_page.php';
	}
}
