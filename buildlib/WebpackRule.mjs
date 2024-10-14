import { Path } from 'esmakefile';
import webpack from 'webpack';

export class WebpackRule {
  _compiler;

  constructor(buildRoot, config, isDevelopment) {
    config.output = {
      path: buildRoot,
    };

    if (isDevelopment) {
      config.mode = 'development';
      config.devtool = config.devtool || 'eval-source-map';
    } else {
      config.mode = 'production';
      config.devtool = config.devtool || 'source-map';
    }

    this._compiler = webpack(config);
  }

  targets() {
    return [Path.build('webpack')];
  }

  recipe(args) {
    return new Promise((resolve, reject) => {
      this._compiler.run((err, result) => {
        if (err) {
          reject(err);
          return;
        }

        args.logStream.write(result.toString({ colors: true }));
        resolve();
      });
    });
  }
}
