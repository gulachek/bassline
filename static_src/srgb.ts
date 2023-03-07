type Hex = string;
type Byte = number;
type RGB = Byte[];
type HSL = number[];

function byteToHex(b: Byte): Hex
{
	return b.toString(16).padStart(2, '0');
}

// map 0-1 to 0-255
function toByte(n: number): Byte
{
	const b = Math.round(n * 255);

	if (b < 0)
		return 0;

	if (b > 255)
		return 255;

	return b;
}


// point in srgb space
export class SRGB
{
	private rgb: RGB;

	// use "fromX" functions instead to be clear
	constructor(rgb: RGB)
	{
		if (rgb.length !== 3)
			throw new Error(`Invalid rgb length: ${rgb.length}`);

		for (const channel of rgb)
		{
			if (channel < 0 || channel > 255)
				throw new Error(`Invalid rgb value: ${channel}`);
		}

		this.rgb = rgb;
	}

	static fromRGB(rgb: RGB): SRGB
	{
		return new SRGB(rgb);
	}

	toRGB(): RGB
	{
		return this.rgb;
	}

	static fromHex(hex: Hex): SRGB
	{
		if (!(/^#[0-9a-fA-F]{6}$/.test(hex)))
			throw new Error(`Invalid hex string: ${hex}`);

		return this.fromRGB([
			parseInt(hex.substr(1, 2), 16),
			parseInt(hex.substr(3, 2), 16),
			parseInt(hex.substr(5, 2), 16)
		]);
	}

	toHex(): Hex
	{
		const rgb = this.toRGB();
		return '#' + rgb.map(b => byteToHex(b)).join('');
	}

	// https://www.rapidtables.com/convert/color/hsl-to-rgb.html
	static fromHSL(hsl: HSL): SRGB
	{
		if (hsl.length !== 3)
			throw new Error(`Invalid hsl length: ${hsl.length}`);

		const [h, s, l] = hsl;

		if (h < 0 || h >= 360)
			throw new Error(`Invalid h: ${h}`);

		if (s < 0 || s > 1)
			throw new Error(`Invalid s: ${s}`);

		if (l < 0 || l > 1)
			throw new Error(`Invalid l: ${l}`);

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

		return this.fromRGB(norm.map(n => toByte(n+m)));
	}

	// https://www.rapidtables.com/convert/color/rgb-to-hsl.html
	toHSL(): HSL
	{
		const rgb = this.toRGB();

		const norm = rgb.map(b => b/255);
		const cmax = Math.max(...norm);
		const cmin = Math.min(...norm);
		const del = cmax - cmin;
		const l = (cmax + cmin)/2;

		let h = 0;
		let s = 0;

		const [rp, gp, bp] = norm;

		if (del)
		{
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

			s = del / (1 - Math.abs(2*l - 1));
		}

		// account for floating point/rounding errors detected in color space
		if (h < 0)
			h += 360;

		s = Math.min(s, 1);

		return [h, s, l];
	}

	equals(srgb: SRGB): boolean
	{
		for (let i = 0; i < 3; ++i)
		{
			if (this.rgb[i] !== srgb.rgb[i])
				return false;
		}

		return true;
	}

	toString(): string
	{
		return this.toHex();
	}

	// from https://www.w3.org/TR/WCAG22/#dfn-relative-luminance
	get luminance(): number
	{
		const norm = this.rgb.map(b => b/255);
		const scaled = norm.map(
			x => (x <= 0.04045) ? x/12.92 : Math.pow((x+0.055)/1.055,  2.4)
		);

		const [r,g,b] = scaled;
		return 0.2126*r + 0.7152*g + 0.0722*b;
	}

	// from https://www.w3.org/TR/WCAG22/#dfn-contrast-ratio
	contrastRatio(srgb: SRGB): number
	{
		const luminance = [this.luminance, srgb.luminance];
		const l1 = Math.max(...luminance);
		const l2 = Math.min(...luminance);

		return (l1 + 0.05) / (l2 + 0.05);
	}
}
