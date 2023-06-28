const { Target, Path, BuildSystem, PathLike } = require('gulpachek');
const { JsonDepfileTarget } = require('./JsonDepfile');
const sass = require('sass');
const fs = require('node:fs');

class ScssTarget extends Target {
  _srcPath;
  _depfile;

  /**
   * @param {BuildSystem} sys
   * @param {PathLike} src
   */
  constructor(sys, src) {
    const srcPath = Path.from(src);
    const components = [...srcPath.components];
    const last = components.pop();
    const destPath = Path.dest(last.replace(/\.scss$/, '.css'));
    super(sys, destPath);
    this._srcPath = srcPath;
    this._depfile = new JsonDepfileTarget(sys, destPath);
  }

  deps() {
    return [this._srcPath, this._depfile];
  }

  recipe(cb) {
    try {
      console.log('sass', this.sys.abs(this._srcPath));
      const result = sass.compile(this.sys.abs(this._srcPath));
      fs.writeFile(this.abs, result.css, cb);

      const deps = result.loadedUrls
        .map((url) => url.pathname)
        .filter((p) => !!p);

      fs.mkdirSync(this.sys.abs(this._depfile.path.dir), { recursive: true });
      fs.writeFileSync(this._depfile.abs, JSON.stringify(deps));
    } catch (ex) {
      cb(ex);
    }
  }
}

module.exports = {
  ScssTarget,
};
