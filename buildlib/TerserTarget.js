const { Target, Path } = require('gulpachek');
const { minify } = require('terser');
const { writeFile, readFile } = require('node:fs/promises');

class TerserTarget extends Target {
	_srcPath;

	constructor(sys, src) {
		const srcPath = Path.from(src);
		const destPath = Path.dest(srcPath.basename);
		super(sys, destPath);
		this._srcPath = srcPath;
	}

	deps() {
		return this._srcPath;
	}

	async recipe()
	{
		console.log('terser', this.sys.abs(this._srcPath));
		const srcCode = await readFile(this.sys.abs(this._srcPath), {
			encoding: 'utf8'
		});
		const result = await minify(srcCode);
		await writeFile(this.abs, result.code);
	}
}

module.exports = {
	TerserTarget
};
