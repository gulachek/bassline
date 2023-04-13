const { BuildSystem, Target, Path } = require('gulpachek');
const webpack = require('webpack');
const webpackConfig = require('./webpack.config');
const path = require('node:path');

const { ScssTarget } = require('./buildlib/ScssTarget');
const { TerserTarget } = require('./buildlib/TerserTarget');

function resolve(p)
{
	return path.resolve(__dirname, p);
}

const sys = new BuildSystem({ buildDir: resolve('assets') });

const requirejs = new TerserTarget(sys, 'static_src/require.js');

const main = new Target(sys);
main.dependsOn(requirejs);

const styles = [
	'main',
	'react_page',
	'group_select',
	'admin_page',
	'login_page',
	'nonce/nonce_login_form',
	'components/tablist',
	'colorPalette/colorPaletteSelect'
];

for (const style of styles)
{
	const target = new ScssTarget(sys, `static_src/${style}.scss`);
	main.dependsOn(target);
}

main.build();
