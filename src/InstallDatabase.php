<?php

namespace Gulachek\Bassline;

class InstallDatabase
{
	public function __construct(
		private Database $db
	)
	{
		$this->db->mountNamedQueries(__DIR__ . '/../sql');
	}

	public static function createInMemory()
	{
		return self::fromSqlite3(new \Sqlite3(':memory:'));
	}

	public static function fromSqlite3(\Sqlite3 $db)
	{
		return new InstallDatabase(new Database($db));
	}

	public static function fromConfig(Config $config): InstallDatabase
	{
		$data_dir = $config->dataDir();
		return self::fromSqlite3(new \Sqlite3("$data_dir/install.db"));
	}

	public function lock(): bool
	{
		return $this->db->lock();
	}

	public function unlock(): void
	{
		$this->db->unlock();
	}

	public function installToVersion(array $apps): ?string
	{
		if ($err = $this->initReentrant())
			return $err;

		$this->db->exec('install/temp-src-code-apps');
		$stmt = $this->db->prepare('insert-src-code-app');

		foreach ($apps as $key => $app)
		{
			$stmt->execWith([
				'app' => $key,
				'semver' => "{$app->version()}"
			]);
		}
		$stmt->close();

		// probably simpler to just prepare a statement in below loop and update
		// table accordingly. this was kinda fun and not bad enough to change
		// right now
		$errs = $this->runInstallOrUpgradeOnApps($apps);

		if (\count($errs))
		{
			$err = '';
			foreach ($errs as $errRow)
				$err .= "{$errRow['err']}\n";
			return $err;
		}

		return null;
	}

	private function initReentrant(): ?string
	{
		if (!$this->db->queryValue('table-exists', 'installed_apps'))
		{
			if (!$this->db->exec('install/init'))
				return 'failed to initialize InstallDatabase';
		}

		return null;
	}

	private function runInstallOrUpgradeOnApps(array $apps): array
	{
		$errors = [];

		$result = $this->db->query('install/compare-src-install-versions');
		$set_version = $this->db->prepare('install/set-version');

		foreach ($result->rows() as $row)
		{
			$key = $row['app'];
			$app = $apps[$key];

			if (\is_null($row['installed_semver']))
			{
				// source code exists, has never been installed
				$err = null;
				try
				{
					$err = $app->install();
				}
				catch (\Exception $ex)
				{
					$err = "caught exception: " . $ex->getMessage();
				}
				finally
				{
					if ($err)
					{
						\array_push($errors, [
							'app' => $key,
							'err' => "Failed to install() app '$key': $err"
						]);
					}
					else
					{
						$set_version->execWith([
							':app' => $key,
							':semver' => $row['src_semver']
						]);
					}
				}
			}
			else if (\is_null($row['src_semver']))
			{
				// was installed, now it isn't. clearly no code to run and we don't know
				// how to clean up, so best we can do is inform the user
				\array_push($errors, [
				 	'app' => $key,
					'err' => "App '$key' was previously installed and still has data on the system, but it is no longer used by this server. Did you rename it?"
				]);
			}
			else
			{
				$installed = Semver::parse($row['installed_semver']);
				$src = Semver::parse($row['src_semver']);

				if ($src->isGreaterThan($installed))
				{
					try
					{
						$err = $app->upgradeFromVersion($installed);
					}
					catch (\Exception $ex)
					{
						$err = "caught exception: " . $ex->getMessage();
					}
					finally
					{
						if ($err)
						{
							\array_push($errors, [
								'app' => $key,
								'err' => "Failed to upgradeFromVersion() app '$key' from '$installed' to '$src': $err"
							]);
						}
						else
						{
							$set_version->execWith([
								':app' => $key,
								':semver' => $row['src_semver']
							]);
						}
					}
				}
				else if ($src->isLessThan($installed))
				{
					\array_push($errors, [
						'app' => $key,
						'err' => "Cannot downgrade app '$key' from '$installed' to '$src'"
					]);
				}
			}

		}

		$set_version->close();
		return $errors;
	}


	// query app key => semver
	public function installedApps(): array
	{
		$result = $this->db->query('install/all-versions');

		$apps = [];
		foreach ($result->rows() as $row)
		{
			$apps[$row['app']] = Semver::parse($row['semver']);
		}

		return $apps;
	}

	public function setVersion(string $app, Semver $version): void
	{
		$this->db->query('install/set-version', [
			':app' => $app,
			':semver' => "$version"
		]);
	}
}
