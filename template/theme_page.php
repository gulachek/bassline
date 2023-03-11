<link rel="stylesheet" href="/static/theme_page.css" />

<?php if ($THEME = $TEMPLATE['theme']): ?>

<dialog id="palette-select-popup">

<?php if (isset($THEME['palette'])): ?>
	<?php foreach ($THEME['palette']['colors'] as $id => $color): ?>
		<div class="color-row">
			<span class="color-row-name"> <?=text($color['name'])?> </span>
			<?php foreach ($TEMPLATE['self']->enumerateShades($color) as $shade): ?>
				<input type="color"
					class="color-button"
					value="<?=$shade->toHex()?>"
					data-color-id="<?=$id?>"
					data-lightness="<?=$shade->toHSL()[2]?>"
				/>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>
<?php endif; ?>

</dialog>

<form method="POST">

<fieldset>
<legend> Theme Colors </legend>
<input type="submit" name="action" value="Add Color" />
<?php foreach ($THEME['themeColors'] as $id => $color): ?>
	<fieldset class="theme-color">
		<input type="hidden"
			name="theme-color-ids[]"
			value="<?=$id?>"
		/>
		<label> Name:
			<input type="text"
				name="theme-color-names[]"
				pattern="<?=$TEMPLATE['name_pattern']?>"
				value="<?=text($color['name'])?>"
			/>
		</label>
		
		<span class="palette-select">
			<label> Background:
				<input class="color-indicator" type="color"
					value="<?=$TEMPLATE['hex'][$color['id']]['bg']?>"
				/>
			</label>
			<input type="hidden"
				class="color-id"
				name="theme-color-bg-colors[]"
				value="<?=$color['bg_color']?>"
			/>
			<input type="hidden"
				class="color-lightness"
				name="theme-color-bg-lightnesses[]"
				value="<?=$color['bg_lightness']?>"
			/>
		</span>
		<span class="palette-select">
			<label> Foreground:
				<input type="color" 
					class="color-indicator"
					value="<?=$TEMPLATE['hex'][$color['id']]['fg']?>"
				/>
			</label>
			<input type="hidden"
				class="color-id"
				name="theme-color-fg-colors[]"
				value="<?=$color['fg_color']?>"
			/>
			<input type="hidden"
				class="color-lightness"
				name="theme-color-fg-lightnesses[]"
				value="<?=$color['fg_lightness']?>"
			/>
		</span>

		<label> Contrast:
			<input type="number"
				class="contrast-indicator"
				readonly
			/>
		</label>
	</fieldset>
<?php endforeach; ?>
</fieldset>

<fieldset>
<legend> Color Mappings </legend>
<?php foreach ($THEME['mappings'] as $id => $mapping): ?>
<div>
	<input type="hidden"
		name="mapping-ids[]"
		value="<?=$mapping['id']?>"
	/>

	<label> <?=text($mapping['app']).'.'.text($mapping['name'])?>:
		<select name="mapping-theme-colors[]" >
			<?php foreach ($THEME['themeColors'] as $id => $color): ?>
				<option
					value="<?=$color['id']?>"
					<?php if ($color['id'] === $mapping['theme_color']): ?>
						selected
					<?php endif; ?>
				>
					<?=text($color['name'])?>
				</option>
			<?php endforeach; ?>
		</select>
	</label>
</div>
<?php endforeach; ?>
</table>
</fieldset>

<fieldset>
<legend> Actions </legend>
<input type="submit" name="action" value="Save" />
</fieldset>

</form>

<script src="/static/srgb.js"></script>
<script src="/static/theme_page.js"></script>

<?php else: ?>

<?php if (count($TEMPLATE['available_themes'])): ?>
<form action="/site/admin/theme/edit">
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

<form method="POST" action="/site/admin/theme/create">
	<fieldset>
	<legend> Create a new theme </legend>
	<input type="submit" value="Create" />
	</fieldset>
</form>

<?php endif; ?>
