const { Target, Path, BuildSystem, PathLike } = require('gulpachek');
const webpack = require('webpack');

class WebpackTarget extends Target {
  _depfile;
  _compiler;

  /**
   * @param {BuildSystem} sys
   * @param {PathLike} dest Destination bundle path
   * @param {webpack.Configuration} config webpack config
   */
  constructor(sys, config) {
    super(sys);
    config.output = {
      path: sys.abs(Path.dest('./')),
    };

    if (sys.isDebugBuild()) {
      config.mode = 'development';
      config.devtool = config.devtool || 'eval-source-map';
    } else {
      config.mode = 'production';
      config.devtool = config.devtool || 'source-map';
    }

    this._compiler = webpack(config);
  }

  recipe(cb) {
    try {
      console.log('webpack running...');
      this._compiler.run((err, result) => {
        if (err) {
          cb(err);
          return;
        }

        console.log(result.toString({ colors: true }));
        cb();
      });
    } catch (ex) {
      cb(ex);
    }
  }
}

module.exports = {
  WebpackTarget,
};
