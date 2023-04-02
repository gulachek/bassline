<h1> Select theme </h1>

<?php if (count($TEMPLATE['available_themes'])): ?>
<form action="<?=$URI->rel('edit')?>">
	<fieldset>
	<legend> Select a theme </legend>
	<select name="id">
		<?php foreach ($TEMPLATE['available_themes'] as $id => $theme): ?>
			<option value="<?=$id?>"> <?=text($theme['name'])?> </option>
		<?php endforeach; ?>
	</select>
	<input type="submit" value="Edit" />
	</fieldset>
</form>
<?php endif; ?>

<form method="POST" action="<?=$URI->rel('create')?>">
	<fieldset>
	<legend> Create a new theme </legend>
	<input type="submit" value="Create" />
	</fieldset>
</form>
