<?php

include __DIR__ . '/../vendor/autoload.php';

if ($argc < 2)
{
	echo "Usage: {$argv[0]} <config>\n";
	exit(1);
}

$server = new \Shell\Server($argv[1]);

if (!$server->initializeSystem())
{
	exit(1);
}
