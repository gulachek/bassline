<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

function isName(string $name): bool
{
	$name_pattern = UserEditPage::USERNAME_PATTERN;
	return preg_match("/^$name_pattern$/", $name);
}

class LandingPage extends Responder
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
				'.' => new LoginPage($this->config, $this->authPlugins()),
				'attempt' => $this->handler('attemptLogin'),
			],
			'logout' => $this->handler('logout'),
			'site' => [ // use this instead of shell
				'admin' => [
					'.' => new AdminPage($this->config),
					'users' => new UserEditPage($this->config, $this->authPlugins()),
					'auth_config' => $this->handler('renderAuthConfig'),
					'groups' => $this->handler('renderGroups'),
					'color_palette' => $this->handler('renderColorPalette'),
					'theme' => $this->handler('serveThemeEdit'),
				]
			],
			'shell' => [
				'theme.css' => $this->handler('serveThemeCss'),
			]
		]);
	}

	public function install(): ?string
	{
		$sec = SecurityDatabase::fromConfig($this->config);

		if ($err = $sec->initReentrant())
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
		$this->syncAppColors($key, $app);
		$this->syncAppCapabilities($key, $app);
	}

	private function syncAppColors(string $key, App $app): void
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

		foreach ($app_colors as $name => $color)
		{
			if (!array_key_exists($name, $existing_colors))
			{
				$db->addSemanticColor($key, $name);
			}
		}

		$db->syncSemanticColors();
	}

	private function syncAppCapabilities(string $key, App $app): void
	{
		$app_caps = $app->capabilities();

		$db = SecurityDatabase::fromConfig($this->config);

		$existing_caps = [];

		$cap_names = $db->capabilityNames($key);
		foreach ($cap_names as $name)
		{
			if (array_key_exists($name, $app_caps))
			{
				$existing_caps[$name] = true;
			}
			else
			{
				$db->removeCapability($key, $name);
			}
		}

		foreach ($app_caps as $name => $cap)
		{
			if (!array_key_exists($name, $existing_caps))
			{
				$db->addCapability($key, $name);
			}
		}

		$db->syncCapabilities();
	}

	private function allAuthPlugins(): array
	{
		$db = SecurityDatabase::fromConfig($this->config);

		return [
			'siwg' => new SignInWithGoogle($db),
			'noauth' => new NoAuthPlugin($db),
			'nonce' => new NoncePlugin($db)
		];
	}

	private function authPlugins(): array
	{
		$all = $this->allAuthPlugins();
		$enabled = [];
		foreach ($all as $key => $plugin)
		{
			if ($plugin->enabled())
				$enabled[$key] = $plugin;
		}

		return $enabled;
	}

	private function allApps(): array
	{
		$apps = ['shell' => $this];
		foreach ($this->config->apps() as $key => $app)
			$apps[$key] = $app;

		return $apps;
	}

	private function allCapabilities(): array
	{
		$db = SecurityDatabase::fromConfig($this->config);
		$caps = $db->loadCapabilities();
		$apps = $this->allApps();
		$merged = [];

		$last_app = null;
		$app_caps = null;

		foreach ($caps as $id => $cap)
		{
			$app_key = $cap['app'];
			$cap_name = $cap['name'];

			if ($last_app !== $app_key)
			{
				$merged[$app_key] = [];
				$app_caps = $apps[$app_key]->capabilities();
				$last_app = $app_key;
			}

			array_push($merged[$app_key], [
				'id' => $id,
				'app' => $app_key,
				'name' => $cap_name,
				'description' => $app_caps[$cap_name]['description']
			]);
		}

		return $merged;
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
			'editable' => [
				'description' => 'Color for elements that are editable (text box)',
				'example-uri' => '/shell/color_palette',
				'default-system-bg' => SystemColor::FIELD,
				'default-system-fg' => SystemColor::FIELD_TEXT
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

	public function capabilities(): array
	{
		return [
			'edit_users' => [
				'description' => 'Create/delete/modify user records. Edit username and auth credentials for any user.'
			],
			'edit_groups' => [
				'description' => 'Create/delete/modify group records. Edit membership and group capabilities.'
			],
			'edit_auth' => [
				'description' => 'Change site-wide authentication configuration.'
			],
			'edit_themes' => [
				'description' => 'Create and edit color palettes and site themes'
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

	public function attemptLogin(): mixed
	{
		$plugin_key = $_REQUEST['auth'] ?? null;
		if (!$plugin_key)
			return new NotFound();

		$redir = $_REQUEST['redirect_uri'] ?? '/';

		$plugins = $this->authPlugins();

		$plugin = $plugins[$plugin_key] ?? null;

		if (!$plugin)
			return new NotFound();

		$db = SecurityDatabase::fromConfig($this->config);
		$user_id = $plugin->authenticate();

		if (!is_null($user_id))
		{
			$token = $db->login($user_id);

			$expire = time() + 30*24*60*60;
			setcookie('login', $token, [
				'expires' => $expire,
				'path' => '/',
				'secure' => isset($_SERVER['HTTPS']),
				'httponly' => true,
				'samesite' => 'Strict'
			]);
		}

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

		return new Redirect('/');
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

	public function renderGroups(RespondArg $arg): mixed
	{
		if (!$arg->userCan('edit_groups'))
		{
			http_response_code(401);
			echo "Not authorized";
			return null;
		}

		$name_pattern = UserEditPage::USERNAME_PATTERN;
		$path = $arg->path;
		$db = SecurityDatabase::fromConfig($this->config);

		if ($path->count() > 1)
			return new NotFound();

		$action = $path->isRoot() ? 'select' : $path->at(0);

		if ($action === 'select')
		{
			$groups = $db->loadGroups();
			if (count($groups) < 1)
			{
				$groups = [$db->createGroup('new_group', $err)];
			}

			$arg->renderPage([
				'template' => __DIR__ . '/../template/group_select.php',
				'title' => 'Select a group',
				'args' => [
					'groups' => $groups,
					'name_pattern' => $name_pattern
				]
			]);
		}
		else if ($action === 'create')
		{
			$groupname = $_REQUEST['groupname'] ?? 'new_group';
			if (!isName($groupname))
			{
				http_response_code(400);
				echo "bad groupname";
				return null;
			}

			$group = $db->createGroup($groupname, $err);
			return new Redirect("/site/admin/groups/edit?id={$group['id']}");
		}
		else if ($action === 'edit')
		{
			$id = intval($_REQUEST['id']);
			$group = $db->loadGroup($id);
			if (!$group)
				return new NotFound();

			$caps = $this->allCapabilities();

			$model = [
				'group' => $group,
				'capabilities' => $caps
			];

			ReactPage::render($arg, [
				'title' => "Edit {$group['groupname']}",
				'scripts' => ['/assets/group_edit.js'],
				'model' => $model
			]);
		}
		else if ($action === 'save')
		{
			$group = $arg->parseBody(Group::class);
			if (!$group)
			{
				http_response_code(400);
				echo "Bad group";
				return null;
			}

			$db->saveGroup($group, $error);

			echo json_encode(['error' => $error]);
		}

		return null;
	}

	public function renderAuthConfig(RespondArg $arg): mixed
	{
		if (!$arg->userCan('edit_auth'))
		{
			http_response_code(401);
			echo "Not authorized";
			return null;
		}

		$path = $arg->path;
		$db = SecurityDatabase::fromConfig($this->config);

		if ($path->count() > 1)
			return new NotFound();

		$action = $path->isRoot() ? 'edit' : $path->at(0);

		$plugins = $this->allAuthPlugins();

		if ($action === 'edit')
		{
			$pluginData = [];
			foreach ($plugins as $key => $plugin)
			{
				if ($data = $plugin->getConfigEditData($key, $db))
				{
					array_push($pluginData, $data);
				}
			}

			$model = [
				'errorMsg' => null,
				'authPlugins' => $pluginData
			];

			ReactPage::render($arg, [
				'title' => 'Authentication Configuration',
				'scripts' => [
					'/assets/require.js',
					'/assets/authConfigEdit.js'
				],
				'model' => $model
			]);
			return null;
		}
		else if ($action === 'save')
		{
			$save = $arg->parseBody(AuthConfigSaveRequest::class);

			foreach ($save->pluginData as $key => $data)
			{
				$p = $plugins[$key];
				if (!$p->invokeSaveConfigEditData($data, $db, $error))
					break;
			}

			echo json_encode([
				'errorMsg' => $error
			]);
			return null;
		}

		return null;
	}

	public function renderColorPalette(RespondArg $arg): mixed
	{
		$db = ColorDatabase::fromConfig($this->config);
		return new ColorPalettePage($db);
	}
}

class AuthConfigSaveRequest
{
	public mixed $pluginData;
}
