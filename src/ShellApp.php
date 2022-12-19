<?php

namespace Shell;

require_once __DIR__ . '/Redirect.php';

class LandingPage extends Page
{
	public function title()
	{
		return 'Landing Page';
	}

	public function body()
	{
		return '<h1>Welcome to my example site</h1>';
	}
}

class ShellApp extends App
{
	public function __construct(
		private Config $config
	)
	{
		parent::__construct(__DIR__ . '/..');
	}

	public function landingPage(): Page
	{
		return new LandingPage();
	}

	public function isShell($info)
	{
		if ($info->isRoot())
			return true;

		$dirs = $this->staticDirs();
		$top = $info->at(0);

		if (array_key_exists($top, $dirs))
		{
			return true;
		}

		return !!array_search($top, ['shell', 'login', 'logout']);
	}

	public function route(PathInfo $path): array
	{
		return [
			'login' => [
				'.' => new LoginPage($this->config->googleClientId()),
				'sign_in_with_google' => $this->handler('attemptLoginWithGoogle')
			],
			'logout' => $this->handler('logout')
		];
	}

	public function attemptLoginWithGoogle()
	{
		$remote_addr = $_SERVER['REMOTE_ADDR'] ?? null;
		if (!$remote_addr)
		{
			http_response_code(500);
			echo "REMOTE_ADDR not set up\n";
			exit;
		}

		// https://www.rfc-editor.org/rfc/rfc5735#section-4
		// This is loopback. not 'localhost' which seems like it
		// could be spoofed.
		$is_loopback = $remote_addr === '127.0.0.1';
		$is_encrypted = isset($_SERVER['HTTPS']);

		if (!($is_encrypted || $is_loopback))
		{
			http_response_code(400);
			echo "login is only supported with encryption\n"; // or loopback shh
			exit;
		}

		$db = new SecurityDatabase(new Database(new \Sqlite3($this->config->loginDatabase())));

		$payload = $db->signInWithGoogle($this->config->googleClientId(), $err);

		if ($err)
		{
			http_response_code(400);
			echo "$err\n";
			exit;
		}

		$token = $db->loginWithGoogle($payload, $err);

		if (!$token)
		{
			http_response_code(400);
			echo "Google signed you in correctly, but we were unable to log you in.\n";
			echo "Contact the system administrator.\n";
			echo "$err\n";
			exit;
		}

		$expire = time() + 30*24*60*60;;
		setcookie('login', $token, [
			'expires' => $expire,
			'path' => '/',
			'secure' => $is_encrypted,
			'httponly' => true,
			'samesite' => 'Strict'
		]);

		$redir = $_REQUEST['redirect_uri'] ?? '/';
		return new Redirect($redir);
	}

	public function logout()
	{
		header('Cache-Control: no-store');

		if (isset($_COOKIE['login']))
		{
			$db = new SecurityDatabase(new Database(new \Sqlite3($this->config->loginDatabase())));
			$db->logout($_COOKIE['login']);
		}

		return new Redirect($_SERVER['HTTP_REFERER']);
	}
}
