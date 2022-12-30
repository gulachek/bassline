<?php
function esc($str): string
{
	return htmlspecialchars($str);
}
?>

<h1> Edit Color Palette </h1>

<form method="POST">

<?php if ($PALETTE): ?>
<input type="hidden" name="palette-id" value="<?= $PALETTE['id'] ?>" />

<fieldset>
<legend> Info </legend>
<label for="palette-name"> Name: </label>
<input type="text"
	id="palette-name"
	name="palette-name"
	value="<?= esc($PALETTE['name']) ?>"
	pattern="<?= $NAME_PATTERN ?>"
	/>
</fieldset>

<fieldset>
<legend> Colors </legend>
<input type="submit" name="action" value="New Color" />

<?php foreach ($PALETTE['colors'] as $color): ?>

<fieldset class="color-fields">

	<input type="hidden"
		name="color-ids[]"
		value="<?= $color['id'] ?>"
		/>

	<div class="color-preview">
	<?php for($i = 0; $i < $SHADE_COUNT; ++$i): ?>
		<input type="color" readonly inert value="<?= $color['hex'] ?>" />
	<?php endfor; ?>
	</div>

	<?php $elem_id = fn($field) => "color-{$field}-{$color['id']}"; ?>

	<div class="color-info">
		<span class="color-name">
		<label for="<?=$elem_id('name')?>"> Name: </label>
		<input type="text"
			id="<?=$elem_id('name')?>"
			name="color-names[]"
			value="<?= $color['name'] ?>"
			pattern="<?= $NAME_PATTERN ?>"
			/>
		</span>

		<span class="color-color">
		<label for="<?=$elem_id('hex')?>"> Color: </label>
		<input type="color"
			name="color-values[]"
			value="<?= $color['hex'] ?>"
			id="<?=$elem_id('hex')?>"
			/>
		</span>
	</div>

</fieldset>

<?php endforeach; ?>

</fieldset>

<fieldset>
<legend> Actions </legend>
<input type="submit" name="action" value="Save" />
</fieldset>

<?php else: ?>


<?php if (count($AVAILABLE_PALETTES)): ?>
	<fieldset>
	<legend> Select an existing palette </legend>
	<select name="palette-id">
	<?php foreach ($AVAILABLE_PALETTES as $palette): ?>
		<option value="<?= $palette['id'] ?>"> <?= esc($palette['name']) ?> </option> 
	<?php endforeach; ?>
	</select>
	<input type="submit" name="action" value="Edit" />
	</fieldset>
<?php endif; ?>

<fieldset>
<legend> Create a new palette </legend>
<label for="new-name"> Palette name: </label>
<input type="text" name="palette-name" value="New Palette" pattern="<?= $NAME_PATTERN ?>" />
<input type="submit" name="action" value="Create" />
</fieldset>


<?php endif; ?>

</form>

<script src="/static/color_palette_page.js"></script>
