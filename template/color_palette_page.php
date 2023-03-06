<?php require_once $UTIL; ?>
<link rel="stylesheet" href="/static/color_palette_page.css" />
<h1> Edit Color Palette </h1>

<form method="POST">

<?php if ($PALETTE = $TEMPLATE['palette']): ?>
<input type="hidden" name="palette-id" value="<?= $PALETTE['id'] ?>" />

<fieldset>
<legend> Info </legend>
<label for="palette-name"> Name: </label>
<input type="text"
	id="palette-name"
	name="palette-name"
	value="<?= text($PALETTE['name']) ?>"
	pattern="<?= $TEMPLATE['name_pattern'] ?>"
	/>
</fieldset>

<fieldset>
<legend> Colors </legend>
<input type="submit" name="action" value="New Color" />

<?php foreach ($PALETTE['colors'] as $id => $color): ?>

<fieldset class="color-fields">

	<input type="hidden"
		name="color-ids[]"
		value="<?=$id?>"
		/>

	<div class="color-preview">
	<?php for($i = 0; $i < $TEMPLATE['shade_count']; ++$i): ?>
		<input type="color" readonly inert value="<?= $color['hex'] ?>" />
	<?php endfor; ?>
	</div>

	<div class="color-info">
		<span class="color-name">
		<label> Name:
		<input type="text"
			name="color-names[]"
			value="<?= $color['name'] ?>"
			pattern="<?= $TEMPLATE['name_pattern'] ?>"
			/>
		</label>
		</span>

		<span class="color-color">
		<label> Color:
		<input type="color"
			name="color-values[]"
			value="<?= $color['hex'] ?>"
			/>
		</label>
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


<?php if (count($TEMPLATE['available_palettes'])): ?>
	<fieldset>
	<legend> Select an existing palette </legend>
	<select name="id">
	<?php foreach ($TEMPLATE['available_palettes'] as $id => $palette): ?>
		<option value="<?=$id?>"> <?= text($palette['name']) ?> </option> 
	<?php endforeach; ?>
	</select>
	<input type="submit" formmethod="GET" formaction="/site/admin/color_palette/edit" value="Edit" />
	</fieldset>
<?php endif; ?>

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


<?php endif; ?>

</form>

<script src="/static/color_palette_page.js"></script>
