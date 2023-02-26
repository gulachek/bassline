<?php

include $_composer_autoload_path ?? __DIR__ . '/../vendor/autoload.php';

// see https://developers.google.com/identity/gsi/web/guides/get-google-api-clientid
header('Referrer-Policy: no-referrer-when-downgrade');
// TODO: remove inline scripts from csp

if (!isset($_SERVER['SITE_CONFIG_PATH']))
{
	$_SERVER['SITE_CONFIG_PATH'] = getenv('SITE_CONFIG_PATH');
}

$server = new \Shell\Server();

// TODO: add a way to generate static content config files and
// opt out of this check via $_SERVER variable to go straight to
// render()
if (!$server->serveStaticContent())
{
	$server->render();
}
