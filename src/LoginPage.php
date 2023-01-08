<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

function origin(string $uri): ?string
{
	$parsed = parse_url($uri);
	if (!$parsed)
		return null;

	$origin = '';

	if (array_key_exists('scheme', $parsed))
	{
		$origin .= "{$parsed['scheme']}://";
	}

	if (array_key_exists('host', $parsed))
	{
		$origin .= $parsed['host'];
	}

	if (array_key_exists('port', $parsed))
	{
		$origin .= ":{$parsed['port']}";
	}

	return $origin;
}

class LoginPage extends Response
{
	public function __construct(
		private string $google_client_id
	)
	{
	}
	
	public function respond(RespondArg $arg): mixed
	{
		$GOOGLE_CLIENT_ID = $this->google_client_id;
		$REFERRER = '/';
		$scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
		$self_origin = "$scheme://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}";
		if (array_key_exists('HTTP_REFERER', $_SERVER))
		{
			$referrer_origin = origin($_SERVER['HTTP_REFERER']);
			if ($self_origin && $self_origin === $referrer_origin)
			{
				$REFERRER = $_SERVER['HTTP_REFERER'];
			}
		}

		$redir_uri = urlencode($REFERRER);
		$SIWG_REQUEST_URI = "$self_origin/login/sign_in_with_google?redirect_uri=$redir_uri";

		$arg->renderPage([
			'title' => 'Log in',
			'template' => __DIR__ . '/../template/login_page.php',
			'args' => [
				'referrer' => $REFERRER,
				'google_client_id' => $GOOGLE_CLIENT_ID,
				'siwg_request_uri' => $SIWG_REQUEST_URI
			]
		]);
		return null;
	}
}
