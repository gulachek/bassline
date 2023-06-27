#!/bin/sh

npm install
npx webpack --mode production
node make.js
