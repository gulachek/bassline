<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

class LandingPage extends Response
{
	public function respond(RespondArg $arg): mixed
	{
		// TODO: make requested app's mounted dir the root for relative paths
		$arg->renderPage([
			'title' => 'Landing Page',
			'template' => __DIR__ . '/../template/landing_page.php'
		]);

		return null;
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

	public function version(): Semver
	{
		return new Semver(0,1,0);
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

		return array_search($top, ['shell', 'site', 'login', 'logout']) !== false;
	}

	public function respond(RespondArg $arg): mixed
	{
		return $arg->route([
			'.' => new LandingPage(),
			'login' => [
				'.' => new LoginPage($this->config->googleClientId()),
				'sign_in_with_google' => $this->handler('attemptLoginWithGoogle')
			],
			'logout' => $this->handler('logout'),
			'site' => [ // use this instead of shell
				'admin' => [
					'.' => new AdminPage($this->config),
					'users' => new UserEditPage($this->config)
				]
			],
			'shell' => [
				'theme' => $this->handler('serveThemeEdit'),
				'color_palette' => new ColorPalettePage($this->config),
				'theme.css' => $this->handler('serveThemeCss'),
				'log_in_as_user' => $this->handler('logInAsUser')
			]
		]);
	}

	public function install(): ?string
	{
		$sec = SecurityDatabase::fromConfig($this->config);

		if ($err = $sec->initReentrant(
			$this->config->adminEmail()
		))
		{
			return "Failed to initialize security database: $err";
		}

		$color = ColorDatabase::fromConfig($this->config);

		if ($err = $color->initReentrant())
		{
			return "Failed to initialize color database: $err";
		}

		return null;
	}

	public function upgradeFromVersion(Semver $version): ?string
	{
		return null;
	}

	// TODO: this should operate on all apps as group instead of individual
	public function installApp(string $key, App $app): void
	{
		$app_colors = $app->colors();

		$db = ColorDatabase::fromConfig($this->config);

		$existing_colors = [];

		$color_names = $db->semanticColorNames($key);
		foreach ($color_names as $name)
		{
			if (array_key_exists($name, $app_colors))
			{
				$existing_colors[$name] = true;
			}
			else
			{
				$db->removeSemanticColor($key, $name);
			}
		}

		$added_colors = [];
		foreach ($app_colors as $name => $color)
		{
			if (!array_key_exists($name, $existing_colors))
			{
				$db->addSemanticColor($key, $name);
			}
		}

		$db->syncSemanticColors();
	}

	private function allApps(): array
	{
		$apps = ['shell' => $this];
		foreach ($this->config->apps() as $key => $app)
			$apps[$key] = $app;

		return $apps;
	}

	public function colors(): array
	{
		return [
			'page' => [
				'description' => 'The default color for every page',
				'example-uri' => '/',
				'default-system-bg' => SystemColor::CANVAS,
				'default-system-fg' => SystemColor::CANVAS_TEXT
			],
			'clickable' => [
				'description' => 'Color for elements that are clickable',
				'example-uri' => '/login',
				'default-system-bg' => SystemColor::BUTTON_FACE,
				'default-system-fg' => SystemColor::BUTTON_TEXT
			],
			'clickable-hover' => [
				'description' => 'Color for hovering elements that are clickable',
				'example-uri' => '/login',
				'default-system-bg' => SystemColor::BUTTON_FACE,
				'default-system-fg' => SystemColor::BUTTON_TEXT
			],
			'selected' => [
				'description' => 'Color for selected interactive elements',
				'example-uri' => '/login',
				'default-system-bg' => SystemColor::SELECTED_ITEM,
				'default-system-fg' => SystemColor::SELECTED_ITEM_TEXT
			],
			'banner' => [
				'description' => 'The website navbar banner color',
				'example-uri' => '/',
				'default-system-bg' => SystemColor::CANVAS,
				'default-system-fg' => SystemColor::CANVAS_TEXT
			],
			'banner-hover' => [
				'description' => 'Color of hovered site-wide navbar items',
				'example-uri' => '/',
				'default-system-bg' => SystemColor::HIGHLIGHT,
				'default-system-fg' => SystemColor::HIGHLIGHT_TEXT
			],
		];
	}

	public function serveThemeEdit()
	{
		$db = ColorDatabase::fromConfig($this->config);

		$colors = [];
		foreach ($this->allApps() as $key => $app)
		{
			if (!isset($colors[$key]))
				$colors[$key] = [];

			foreach ($app->colors() as $name => $def)
				$colors[$key][$name] = new Color($key, $name, $def);
		}

		return new ThemeEditPage($db, $colors);
	}

	public function logInAsUser(): mixed
	{
		$user_id = intval($_REQUEST['user-id'] ?? '0');

		$db = SecurityDatabase::fromConfig($this->config);
		$token = $db->login($user_id);

		if (!$token)
		{
			http_response_code(401);
			echo "Failed to log in as $user_id";
			return null;
		}

		$expire = time() + 30*24*60*60;
		setcookie('login', $token, [
			'expires' => $expire,
			'path' => '/',
			'secure' => false,
			'httponly' => true,
			'samesite' => 'Strict'
		]);

		$redir = $_REQUEST['redirect-uri'] ?? '/';
		return new Redirect($redir);
	}

	public function attemptLoginWithGoogle(): mixed
	{
		$remote_addr = $_SERVER['REMOTE_ADDR'] ?? null;
		if (!$remote_addr)
		{
			http_response_code(500);
			echo "REMOTE_ADDR not set up\n";
			return null;
		}

		// https://www.rfc-editor.org/rfc/rfc5735#section-4
		// This is loopback. not 'localhost'. Haven't thought
		// hard enough if this matters and could be spoofed.
		$is_loopback = $remote_addr === '127.0.0.1'
			|| $remote_addr === '::1'; // doesn't cover all of ipv6 but good enough for dev server

		$is_encrypted = isset($_SERVER['HTTPS']);

		if (!($is_encrypted || $is_loopback))
		{
			http_response_code(400);
			echo "login is only supported with encryption\n"; // or loopback shh
			return null;
		}

		$db = SecurityDatabase::fromConfig($this->config);

		$payload = $db->signInWithGoogle($this->config->googleClientId(), $err);

		if ($err)
		{
			http_response_code(400);
			echo "$err\n";
			return null;
		}

		$token = $db->loginWithGoogle($payload, $err);

		if (!$token)
		{
			http_response_code(400);
			echo "Google signed you in correctly, but we were unable to log you in.\n";
			echo "Contact the system administrator.\n";
			echo "$err\n";
			return null;
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
			$db = SecurityDatabase::fromConfig($this->config);
			$db->logout($_COOKIE['login']);
		}

		return new Redirect($_SERVER['HTTP_REFERER']);
	}

	private static function mappedThemeColorsForApp(array $theme, string $app_key): array
	{
		$by_name = [];
		foreach ($theme['mappings'] as $id => $mapping)
		{
			if ($mapping['app'] === $app_key
				&& is_int($mapping['theme_color']))
			{
				$by_name[$mapping['name']] = $mapping['theme_color'];
			}
		}

		return $by_name;
	}

	private static function mapAppThemeColors(?array $theme, string $app_key, array $colors): array
	{
		$by_name = [];

		if ($theme)
			$by_name = self::mappedThemeColorsForApp($theme, $app_key);

		$name_to_css = [];

		foreach ($colors as $name => $def)
		{
			$color = new Color($app_key, $name, $def);
			$bg = $color->defaultBg();
			$fg = $color->defaultFg();

			if (array_key_exists($name, $by_name))
			{
				$theme_color_id = $by_name[$name];
				$theme_color = $theme['theme-colors'][$theme_color_id];

				if (is_int($theme_color['bg_color']))
				{
					$lightness = $theme_color['bg_lightness'];

					$palette = $theme['palette']['colors']
						[$theme_color['bg_color']];
					$srgb_base = SRGB::fromHex($palette['hex']);
					list($h,$s,$l) = $srgb_base->toHSL();
					$bg = SRGB::fromHSL([$h,$s, $lightness])->toHex();
				}

				if (is_int($theme_color['fg_color']))
				{
					$lightness = $theme_color['fg_lightness'];

					$palette = $theme['palette']['colors']
						[$theme_color['fg_color']];
					$srgb_base = SRGB::fromHex($palette['hex']);
					list($h,$s,$l) = $srgb_base->toHSL();
					$fg = SRGB::fromHSL([$h,$s, $lightness])->toHex();
				}
			}

			$name_to_css[$name] = [
				'bg' => $bg,
				'fg' => $fg
			];
		}

		return $name_to_css;
	}

	public function serveThemeCss()
	{
		if (empty($_REQUEST['app']))
		{
			http_response_code(400);
			echo "App not specified\n";
			return null;
		}

		$app_key = strtolower($_REQUEST['app']);
		$apps = $this->allApps();

		if (!array_key_exists($app_key, $apps))
		{
			http_response_code(404);
			echo "App not found\n";
			return null;
		}

		$app = $apps[$app_key];
		$colors = $app->colors();

		$db = ColorDatabase::fromConfig($this->config);

		$active_themes = $db->getActiveThemes();

		$dark_theme = null;
		$light_theme = null;

		if ($active_themes['dark'] ?? false)
		{
			$dark_theme = $db->loadTheme($active_themes['dark']);
		}

		if ($active_themes['light'] ?? false)
		{
			$light_theme = $db->loadTheme($active_themes['light']);
		}

		$DARK = self::mapAppThemeColors($dark_theme, $app_key, $colors);
		$LIGHT = self::mapAppThemeColors($light_theme, $app_key, $colors);

		header('Content-Type: text/css');
		require (__DIR__ . '/../template/theme.css.php');
		return null;
	}

}
