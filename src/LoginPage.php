<?php

namespace Shell;

require_once __DIR__ . '/Page.php';

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

class LoginPage extends Page
{
	public function __construct(
		private string $google_client_id
	)
	{
	}
	
	public function title(): string
	{
		return 'Log in';
	}

	public function body(): void
	{
		$GOOGLE_CLIENT_ID = $this->google_client_id;
		$referrer = '/';
		if (array_key_exists('HTTP_REFERER', $_SERVER))
		{
			$self_origin = origin($_SERVER['REQUEST_URI']);
			$referrer_origin = origin($_SERVER['HTTP_REFERER']);
			if ($self_origin && $self_origin === $referrer_origin)
			{
				$referrer = $_SERVER['HTTP_REFERER'];
			}
		}

		$redir_uri = urlencode($referrer);
		$SIWG_REQUEST_URI = "/login/sign_in_with_google?redirect_uri=$redir_uri";

		include __DIR__ . '/../template/login_page.php';
	}

	public function stylesheets(): array
	{
		return ['/static/login_page.css'];
	}
}
