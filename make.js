const { BuildSystem, Target, Path } = require('gulpachek');
const webpack = require('webpack');
const webpackConfig = require('./webpack.config');
const sass = require('sass');
const path = require('node:path');
const fs = require('node:fs');

function resolve(p)
{
	return path.resolve(__dirname, p);
}

class WebpackTarget extends Target {
	#config;

	constructor(sys, config) {
		super(sys);
		this.#config = config;

		if (!this.#config.mode)
			this.#config.mode = sys.isDebugBuild() ? 'development' : 'production';
	}

	recipe(cb) {
		console.log('webpack');
		webpack(this.#config, (err, stats) => {
			if (err) {
				cb(err);
				return;
			}

			const info = stats.toJson();

			if (stats.hasErrors()) {
				console.error(info.errors);
				cb(new Error('webpack errors. see error log for details'));
				return;
			}

			if (stats.hasWarnings()) {
				console.warn(info.warnings);
			}

			cb();
		});
	}
}

class ScssTarget extends Target {
	_srcPath;

	constructor(sys, src) {
		const srcPath = Path.from(src);
		const components = [...srcPath.components];
		const last = components.pop();
		const destPath = Path.dest(last.replace(/\.scss$/, '.css'));
		super(sys, destPath);
		this._srcPath = srcPath;
	}

	deps() {
		return this._srcPath;
	}

	recipe(cb)
	{
		try {
			console.log('sass', this.sys.abs(this._srcPath));
			const result = sass.compile(this.sys.abs(this._srcPath));
			fs.writeFile(this.abs, result.css, cb);
		} catch (ex) {
			cb(ex);
		}
	}
}

const sys = new BuildSystem({ buildDir: resolve('assets') });
const webpackTarget = new WebpackTarget(sys, webpackConfig);

const groupSelectStyle = new ScssTarget(sys, 'static_src/group_select.scss');

const main = new Target(sys);
main.dependsOn(/*webpackTarget, */groupSelectStyle);

main.build();
