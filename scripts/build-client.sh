#!/bin/sh

USAGE="Usage: $0 <install|update>"

NPM_COMMAND=${1:?$USAGE}

npm $NPM_COMMAND
node make.js
npx webpack --mode production
