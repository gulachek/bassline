const { Target, Path, BuildSystem, PathLike } = require('gulpachek');
const fs = require('node:fs');

class JsonDepfileTarget extends Target {
  _srcPath;

  /**
   * @param {BuildSystem} sys The build system
   * @param {PathLike} dest The path representing the generated file whose dependencies this target tracks
   */
  constructor(sys, dest) {
    const destPath = Path.dest(dest);
    super(
      sys,
      destPath.gen({
        namespace: 'com.gulachek.bassline', // TODO should change if put into npm package
        ext: 'deps.json',
      })
    );
  }

  recipe(cb) {
    console.log('JsonDepfileTarget', this.abs);
    cb();
  }

  mtime() {
    const zero = new Date(0);
    const path = this.abs;
    if (!fs.existsSync(path)) return zero; // nothing to depend on

    const json = fs.readFileSync(path, 'utf8');
    const entries = JSON.parse(json);

    let maxAge = zero;
    for (const f of entries) {
      try {
        const age = fs.statSync(f).mtime;
        maxAge = maxAge < age ? age : maxAge;
      } catch (e) {
        e.message += `: ${f}`;
        throw e;
      }
    }

    return maxAge;
  }
}

module.exports = {
  JsonDepfileTarget,
};
