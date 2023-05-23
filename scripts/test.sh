#!/bin/sh

echo "=============================="
echo "Running phpunit..."
echo "=============================="
vendor/bin/phpunit test/phpunit || exit 1

echo "=============================="
echo "Running uitest.py..."
echo "=============================="
test/uitest.py || exit 1
