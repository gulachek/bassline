#!/bin/sh

BIN_DIR=$(dirname "$0")
pushd "$BIN_DIR/.."
	npm install
	node make.js
	npx webpack --mode production
popd
