import * as path from 'node:path';

const { pathname } = new URL(import.meta.url);
const __dirname = path.dirname(pathname);

function resolve(p) {
  return path.resolve(__dirname, p);
}

export default {
  entry: {
    react: ['react', 'react-dom'],
    user_edit: {
      import: resolve('static_src/user_edit.tsx'),
      filename: '[name].js',
      dependOn: ['react'],
    },
    siwg_edit: {
      import: resolve('static_src/siwg_edit.tsx'),
      library: {
        type: 'amd',
      },
      dependOn: ['react'],
    },
    group_edit: {
      import: resolve('static_src/group_edit.tsx'),
      filename: '[name].js',
      dependOn: ['react'],
    },
    colorPaletteEdit: {
      import: resolve('static_src/colorPalette/colorPaletteEdit.tsx'),
      filename: '[name].js',
      dependOn: ['react'],
    },
    themeEdit: {
      import: resolve('static_src/themeEdit/themeEdit.tsx'),
      filename: '[name].js',
      dependOn: ['react'],
    },
    authConfigEdit: {
      import: resolve('static_src/authConfigEdit/authConfigEdit.tsx'),
      filename: '[name].js',
      dependOn: ['react'],
    },
    noauthConfigEdit: {
      import: resolve('static_src/noauth/noauthConfigEdit.tsx'),
      library: {
        type: 'amd',
      },
      dependOn: ['react'],
    },
    nonceConfigEdit: {
      import: resolve('static_src/nonce/nonceConfigEdit.tsx'),
      library: {
        type: 'amd',
      },
      dependOn: ['react'],
    },
    siwgConfigEdit: {
      import: resolve('static_src/siwg/siwgConfigEdit.tsx'),
      library: {
        type: 'amd',
      },
      dependOn: ['react'],
    },
    components: {
      import: [
        resolve('static_src/components/loading_overlay.ts'),
        resolve('static_src/components/tablist.ts'),
      ],
      filename: 'components.js',
    },
  },
  module: {
    rules: [
      {
        test: /\.tsx?$/,
        use: 'ts-loader',
        exclude: /node_modules/,
      },
      {
        test: /\.s?css$/i,
        use: [
          {
            loader: 'style-loader',
            options: { injectType: 'linkTag' },
          },
          {
            loader: 'file-loader',
            options: { name: '[name].css' },
          },
          'sass-loader',
        ],
      },
    ],
  },
  resolve: {
    extensions: ['.tsx', '.ts', '.js'],
  },
};
