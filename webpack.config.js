const path = require('path');

function resolve(p)
{
	return path.resolve(__dirname, p);
}

module.exports = {
	entry: {
		react: ['react', 'react-dom'],
		user_edit: {
			import: resolve('static_src/user_edit.tsx'),
			filename: '[name].js',
			dependOn: ['react']
		},
		siwg_edit: {
			import: resolve('static_src/siwg_edit.tsx'),
			library: {
				type: 'amd'
			},
			dependOn: ['react']
		},
		group_edit: {
			import: resolve('static_src/group_edit.tsx'),
			filename: '[name].js',
			dependOn: ['react']
		},
		authConfigEdit: {
			import: resolve('static_src/authConfigEdit/authConfigEdit.tsx'),
			filename: '[name].js',
			dependOn: ['react']
		},
		noauthConfigEdit: {
			import: resolve('static_src/noauth/noauthConfigEdit.tsx'),
			library: {
				type: 'amd'
			},
			dependOn: ['react']
		},
		siwgConfigEdit: {
			import: resolve('static_src/siwg/siwgConfigEdit.tsx'),
			library: {
				type: 'amd'
			},
			dependOn: ['react']
		},
		components: {
			import: [resolve('static_src/components/loading_overlay.ts')],
			filename: 'components.js',
		},
	},
	module: {
		rules: [
			{
				test: /\.tsx?$/,
				use: 'ts-loader',
				exclude: /node_modules/
			},
			{
				test: /\.s?css$/i,
				use: [
					{
						loader: "style-loader",
						options: { injectType: "linkTag" }
					},
					{
						loader: "file-loader",
						options: { name: '[name].css' },
					},
					"sass-loader",
				],
					                                                                 },
		]
	},
	resolve: {
		extensions: ['.tsx', '.ts', '.js'],
	},
	output: {
		path: path.resolve(__dirname, 'assets')
	}
};
