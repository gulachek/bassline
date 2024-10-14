import { cli, Path } from 'esmakefile';
import webpackConfig from './webpack.config.mjs';
import { readFile, writeFile } from 'node:fs/promises';
import { minify } from 'terser';
import sass from 'sass';
import { WebpackRule } from './buildlib/WebpackRule.mjs';

const requirejsSrc = Path.src('static_src/require.js');
const requirejs = Path.build('require.js');

const styles = [
  'main',
  'react_page',
  'group_select',
  'admin_page',
  'login_page',
  'error_page',
  'nonce/nonce_login_form',
  'components/tablist',
  'colorPalette/colorPaletteSelect',
];

cli((make, opts) => {
  make.add('all', [requirejs]);

  make.add(requirejs, requirejsSrc, async (args) => {
    const contents = await readFile(args.abs(requirejsSrc), 'utf8');
    const result = await minify(contents);
    await writeFile(args.abs(requirejs), result.code, 'utf8');
  });

  for (const style of styles) {
    const scss = Path.src(`static_src/${style}.scss`);
    const css = Path.build(scss.basename.replace('.scss', '.css'));

    make.add('all', css);

    make.add(css, scss, async (args) => {
      const result = sass.compile(args.abs(scss));
      await writeFile(args.abs(css), result.css, 'utf8');

      for (const url of result.loadedUrls) {
        const p = url.pathname;
        p && args.addPostreq(p);
      }
    });
  }

  make.add(new WebpackRule(make.buildRoot, webpackConfig, opts.isDevelopment));
  make.add('all', 'webpack');
});
