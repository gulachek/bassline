#!/bin/sh

CONFIG=test/config.php

rm test/data/playground/*.db
php bin/init.php "$CONFIG"
php bin/issue_nonce.php "$CONFIG" admin | pbcopy
