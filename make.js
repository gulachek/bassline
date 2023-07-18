const { BuildSystem, Target, Path } = require('gulpachek');
const webpackConfig = require('./webpack.config');
const path = require('node:path');
const fs = require('node:fs');
const { program } = require('commander');

const { ScssTarget } = require('./buildlib/ScssTarget');
const { TerserTarget } = require('./buildlib/TerserTarget');
const { WebpackTarget } = require('./buildlib/WebpackTarget');

function resolve(p) {
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
  'error_page',
  'nonce/nonce_login_form',
  'components/tablist',
  'colorPalette/colorPaletteSelect',
];

for (const style of styles) {
  const target = new ScssTarget(sys, `static_src/${style}.scss`);
  main.dependsOn(target);
}

const webpackTarget = new WebpackTarget(sys, webpackConfig);
main.dependsOn(webpackTarget);

program
  .command('build', { isDefault: true })
  .description('Build all targets and exit')
  .action(() => {
    main.build();
  });

program
  .command('watch')
  .description(
    'Watch the filesystem for changes in the source directory and rebuild as needed'
  )
  .action(() => {
    main.build();

    const srcPath = sys.abs('static_src');
    console.log('watching ', srcPath);

    fs.watch(srcPath, { recursive: true }, (event, filename) => {
      const ext = path.extname(filename);
      switch (ext) {
        case '.tsx':
        case '.scss':
        case '.ts':
          break;
        default:
          return;
      }

      console.log(event, path.resolve(filename));
      main.build();
    });
  });

program.parse();
