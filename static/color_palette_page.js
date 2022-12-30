function updatePreview(preview, colorEl)
{
	const hex = colorEl.value;
	const rgb = hexToRgb(hex);
	const [h,s,l] = rgbToHsl(rgb);

	const children = preview.children;
	const n = children.length;

	const max = 0.975;
	const min = 0.025;
	const del = (max - min) / (n-1);

	for (let i = 0; i < n; ++i)
	{
		children[i].value = rgbToHex(hslToRgb([h,s, i*del + min]));
	}
}

function hexToRgb(hex)
{
	return [
		parseInt(hex.substr(1, 2), 16),
		parseInt(hex.substr(3, 2), 16),
		parseInt(hex.substr(5, 2), 16)
	];
}

function byteToHex(b)
{
	return b.toString(16).padStart(2, '0');
}

function rgbToHex(rgb)
{
	return '#' + rgb.map(b => byteToHex(b)).join('');
}

// https://www.rapidtables.com/convert/color/rgb-to-hsl.html
function rgbToHsl(rgb)
{
	const norm = rgb.map(b => b/255);
	const [rp, gp, bp] = norm;
	const cmax = Math.max(...norm);
	const cmin = Math.min(...norm);
	const del = cmax - cmin;
	const l = (cmax + cmin)/2;

	let h = 0;
	let s = 0;

	switch (cmax)
	{
		case rp:
			h = 60 * (((gp-bp)/del) % 6);
			break;
		case gp:
			h = 60 * (((bp-rp)/del) + 2);
			break;
		case bp:
			h = 60 * (((rp-gp)/del) + 4);
			break;
		default:
			throw new Error('this was not expected');
			break;
	}

	if (del === 0)
	{
		h = 0;
		s = 0;
	}
	else
	{
		s = del / (1 - Math.abs(2*l - 1));
	}

	if (h < 0)
		h += 360;

	return [h, s, l];
}

// map 0-1 to 0-255
function toByte(n)
{
	let b = Math.round(n * 255);

	if (b < 0)
		return 0;

	if (b > 255)
		return 255;

	return b;
}

// https://www.rapidtables.com/convert/color/hsl-to-rgb.html
function hslToRgb(hsl)
{
	const [h, s, l] = hsl;
	const c = (1 - Math.abs(2*l - 1)) * s;
	const x = c * (1 - Math.abs(((h/60) % 2) - 1));
	const m = l - c/2;

	let norm = [0,0,0];

	if (0 <= h && h < 60)
		norm = [c,x,0];
	else if (60 <= h && h < 120)
		norm = [x,c,0];
	else if (120 <= h && h < 180)
		norm = [0,c,x];
	else if (180 <= h && h < 240)
		norm = [0,x,c];
	else if (240 <= h && h < 300)
		norm = [x,0,c];
	else
		norm = [c,0,x];

	return norm.map(n => toByte(n+m));
}

const colorFields = document.querySelectorAll('.color-fields');

for (const colorField of colorFields)
{
	const color = colorField.querySelector('input[name="color-values[]"]');
	const preview = colorField.querySelector('.color-preview');

	color.addEventListener('input', updatePreview.bind(null, preview, color));
	updatePreview(preview, color);
}
