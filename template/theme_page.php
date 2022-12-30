<?php

function esc(?string $s): string
{ return htmlspecialchars($s ?? ""); }

?>

<?php if ($THEME): ?>

<dialog id="palette-select-popup">

<?php if (isset($THEME['palette'])): ?>
	<?php foreach ($THEME['palette']['colors'] as $id => $color): ?>
		<div class="color-row">
			<span class="color-row-name"> <?=esc($color['name'])?> </span>
			<?php foreach (self::enumerateShades($color) as $shade): ?>
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

<input type="hidden" name="theme-id" value="<?=$THEME['id']?>" />

<fieldset>
<legend> Theme </legend>
<label> Name:
<input type="text"
	name="theme-name"
	pattern="<?=$NAME_PATTERN?>"
	value="<?=esc($THEME['name'])?>"
	/>
</label>
<br />

<label> Palette:
	<input type="text" readonly
<?php if (isset($THEME['palette'])): ?>
	value="<?=esc($THEME['palette']['name'])?>"

<?php else: ?>
	value="No palette selected"
<?php endif; ?>
</label>

<dialog id="change-palette-popup">
	<h1> Change Palette </h1>
	<p>
		Changing your palette will lose all unsaved progress in the theme
		and all user color selections will be lost.
	</p>
	<label> Palette:
	<select id="theme-palette" name="theme-palette">
		<?php foreach ($AVAILABLE_PALETTES as $id => $palette): ?>
			<option value="<?=$id?>">
				<?=esc($palette['name'])?>
			</option>
		<?php endforeach; ?>
	</select>
	</label>
	<br />
	<input type="submit" name="action" value="Change Palette" />
</dialog>
<button id="change-palette-button"> Change </button>

<div>
	<?php foreach(['inactive', 'dark', 'light'] as $status): ?>
		<label>
		<input type="radio"
			name="theme-status"
			value="<?=$status?>"
			<?php if($status === $STATUS): ?>
				checked
			<?php endif; ?>
			/> <?=ucwords($status)?>
		</label>
	<?php endforeach; ?>
</div>


</fieldset>

<fieldset>
<legend> Theme Colors </legend>
<input type="submit" name="action" value="Add Color" />
<?php foreach ($THEME['theme-colors'] as $id => $color): ?>
	<fieldset class="theme-color">
		<input type="hidden"
			name="theme-color-ids[]"
			value="<?=$id?>"
		/>
		<label> Name:
			<input type="text"
				name="theme-color-names[]"
				pattern="<?=$NAME_PATTERN?>"
				value="<?=esc($color['name'])?>"
			/>
		</label>
		
		<span class="palette-select">
			<label> Background:
				<input class="color-indicator" type="color"
					value="<?=$THEME_COLOR_HEX[$color['id']]['bg']?>"
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
					value="<?=$THEME_COLOR_HEX[$color['id']]['fg']?>"
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

	<label> <?=esc($mapping['app']).'.'.esc($mapping['name'])?>:
		<select name="mapping-theme-colors[]" >
			<?php foreach ($THEME['theme-colors'] as $id => $color): ?>
				<option
					value="<?=$color['id']?>"
					<?php if ($color['id'] === $mapping['theme_color']): ?>
						selected
					<?php endif; ?>
				>
					<?=esc($color['name'])?>
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

<form method="POST">
<?php if (count($AVAILABLE_THEMES)): ?>
<fieldset>
<legend> Select a theme </legend>
<select name="theme-id">
	<?php foreach ($AVAILABLE_THEMES as $id => $theme): ?>
		<option value="<?=$id?>"> <?=esc($theme['name'])?> </option>
	<?php endforeach; ?>
</select>
<input type="submit" name="action" value="Edit" formmethod="GET" />
</fieldset>
<?php endif; ?>

<fieldset>
<legend> Create a new theme </legend>
<input type="submit" name="action" value="Create" />
</fieldset>
</form>

<?php endif; ?>
