function showChangeThemeDialog(e)
{
	const changePaletteDialog = document.getElementById('change-palette-popup');

	changePaletteDialog.showModal();

	// don't want to submit the form
	e.preventDefault();
}

class PaletteSelectPopup
{
	dialog;
	resolve;
	result;

	constructor()
	{
		this.dialog = document.getElementById('palette-select-popup'); 
		this.resolve = null;
		this.result = null;

		this.dialog.addEventListener('close', this.onDialogClose.bind(this));

		for (const color of this.dialog.querySelectorAll('.color-button'))
		{
			color.addEventListener('click', this.clickColorButton.bind(this));
		}
	}

	async chooseColor()
	{
		this.dialog.showModal();
		return new Promise((res) => { this.resolve = res; });
	}

	clickColorButton(e)
	{
		e.preventDefault();

		const { colorId, lightness } = e.target.dataset;
		const hex = e.target.value;

		this.result = { colorId, lightness, hex };
		this.dialog.close();
	}

	onDialogClose(e)
	{
		const result = this.result;
		this.result = null;

		if (!this.resolve)
		{
			throw new Error('dialog should only be open after promise set up');
		}

		const resolve = this.resolve;
		this.resolve = null;
		resolve(result);
	}
}

function updateContrast(themeColor)
{
	const indicators = themeColor.querySelectorAll('.color-indicator');
	if (indicators.length !== 2)
		throw new Error('expected 2 color indicators in theme color selection');

	const first = SRGB.fromHex(indicators[0].value);
	const second = SRGB.fromHex(indicators[1].value);

	themeColor.querySelector('.contrast-indicator').value = 
		Math.round(first.contrastRatio(second) * 10) / 10;
}

async function selectPaletteColor(paletteSelect, popup, themeColor, e)
{
	e.preventDefault(); // don't want to actually use default color select

	const selection = await popup.chooseColor();
	if (!selection)
		return;

	const { colorId, lightness, hex } = selection;

	e.target.value = hex;
	paletteSelect.querySelector('.color-id').value = colorId;
	paletteSelect.querySelector('.color-lightness').value = lightness;

	updateContrast(themeColor);
}

const changePaletteBtn = document.getElementById('change-palette-button');
changePaletteBtn.addEventListener('click', showChangeThemeDialog);

const popup = new PaletteSelectPopup();

const themeColors = document.querySelectorAll('.theme-color');
for (const themeColor of themeColors)
{
	const paletteSelects = themeColor.querySelectorAll('.palette-select');
	for (const paletteSelect of paletteSelects)
	{
		const color = paletteSelect.querySelector('.color-indicator');
		color.addEventListener('click',
			selectPaletteColor.bind(null, paletteSelect, popup, themeColor));
	}

	updateContrast(themeColor);
}
