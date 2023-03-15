<?php

include $_composer_autoload_path ?? __DIR__ . '/../vendor/autoload.php';

if ($argc < 3)
{
	echo "Usage: {$argv[0]} <config> <username>\n";
	exit(1);
}

$server = new \Gulachek\Bassline\Server($argv[1]);

if (!$server->issueNonce($argv[2]))
{
	exit(1);
}
