<?php

include __DIR__ . '/../vendor/autoload.php';

if ($argc < 3)
{
	echo "Usage: {$argv[0]} <config> <username>\n";
	exit(1);
}

$server = new \Shell\Server($argv[1]);

if (!$server->issueNonce($argv[2]))
{
	exit(1);
}
