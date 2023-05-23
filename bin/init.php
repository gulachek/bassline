<?php

include $_composer_autoload_path ?? __DIR__ . '/../vendor/autoload.php';

$prjDir = \Composer\InstalledVersions::getInstallPath('gulachek/bassline');

if (!$prjDir)
{
	echo "Cannot locate root directory of gulachek/bassline";
	exit(1);
}

if ($argc < 2)
{
	echo "Usage: {$argv[0]} <config> [--skip-client-build]\n";
	exit(1);
}

$server = new \Gulachek\Bassline\Server($argv[1]);

if (!$server->initializeSystem())
{
	exit(1);
}

if ($argc < 3 || $argv[2] !== '--skip-client-build')
	\system("$prjDir/bin/build-client.sh");
