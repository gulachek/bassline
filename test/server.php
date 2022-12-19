<?php

// see https://developers.google.com/identity/gsi/web/guides/get-google-api-clientid
header('Referrer-Policy: no-referrer-when-downgrade');
// TODO: remove inline scripts from csp

$_SERVER['SITE_CONFIG_PATH'] = __DIR__ . '/config.php';

include __DIR__ . '/../vendor/autoload.php';

// For testing purposes only... should use script to iterate over static dirs and
// generate web server config to point to static locations to avoid FastCGI request
// overhead in production environments
$server = new \Shell\Server();

if (!$server->serveStaticContent())
{
	$server->render();
}
