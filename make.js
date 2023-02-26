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

const groupSelectStyle = new ScssTarget(sys, 'static_src/group_select.scss');
const requirejs = new TerserTarget(sys, 'static_src/require.js');

const main = new Target(sys);
main.dependsOn(groupSelectStyle, requirejs);

main.build();
