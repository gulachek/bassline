<link rel="stylesheet" href="/assets/colorPaletteSelect.css" />
<h1> Select color palette </h1>

<?php if (count($TEMPLATE['available_palettes'])): ?>
<form action="/site/admin/color_palette/edit">
	<fieldset>
	<legend> Select an existing palette </legend>
	<select name="id">
	<?php foreach ($TEMPLATE['available_palettes'] as $id => $palette): ?>
		<option value="<?=$id?>"> <?= text($palette['name']) ?> </option> 
	<?php endforeach; ?>
	</select>
	<input type="submit" value="Edit" />
	</fieldset>
</form>
<?php endif; ?>

<form method="POST" action="/site/admin/color_palette/create">
	<fieldset>
	<legend> Create a new palette </legend>
	<label> Palette name:
		<input type="text"
			name="palette-name"
			value="New Palette"
			pattern="<?=$TEMPLATE['name_pattern']?>"
		/>
	</label>
	<input type="submit" name="action" value="Create" />
	</fieldset>
</form>
