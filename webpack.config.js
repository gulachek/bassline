const path = require('path');

module.exports = {
	entry: {
		user_edit: path.resolve(__dirname, 'static_src/user_edit.tsx')
	},
	module: {
		rules: [
			{
				test: /\.tsx?$/,
				use: 'ts-loader',
				exclude: /node_modules/
			}
		]
	},
	resolve: {
		extensions: ['.tsx', '.ts', '.js'],
	},
	output: {
		filename: '[name].js',
		path: path.resolve(__dirname, 'assets')
	}
};
