<?php

# Do this for better performance
if (isset($_SERVER['AUTOLOAD_PATH']))
{
	require_once $_SERVER['AUTOLOAD_PATH'];
}
else # nicer for development
{
	$dir = __DIR__;
	while (!file_exists("$dir/vendor/autoload.php"))
	{
		$next = dirname($dir);
		if ($next == $dir)
		{
			throw new \Exception("vendor/autoload.php not found in ancestor directory");
		}
		$dir = $next;
	}

	require_once "$dir/vendor/autoload.php";
}

// see https://developers.google.com/identity/gsi/web/guides/get-google-api-clientid
header('Referrer-Policy: no-referrer-when-downgrade');
// TODO: remove inline scripts from csp

if (!isset($_SERVER['SITE_CONFIG_PATH']))
{
	$_SERVER['SITE_CONFIG_PATH'] = getenv('SITE_CONFIG_PATH');
}

$server = new \Gulachek\Bassline\Server();

// TODO: add a way to generate static content config files and
// opt out of this check via $_SERVER variable to go straight to
// render()
if (!$server->serveStaticContent())
{
	$server->render();
}
