const { BuildSystem, Target } = require('gulpachek');
const webpack = require('webpack');
const webpackConfig = require('./webpack.config');

class WebpackTarget extends Target {
	#config;

	constructor(sys, config) {
		super(sys);
		this.#config = config;

		if (!this.#config.mode)
			this.#config.mode = sys.isDebugBuild() ? 'development' : 'production';
	}

	recipe(cb) {
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

const sys = new BuildSystem();
const webpackTarget = new WebpackTarget(sys, webpackConfig);

webpackTarget.build();
