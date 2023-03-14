<?php

namespace Shell;

class InstallDatabase
{
	public function __construct(
		private Database $db
	)
	{
		$this->db->mountNamedQueries(__DIR__ . '/../sql');
	}

	static function fromConfig(Config $config): InstallDatabase
	{
		$path = "{$config->dataDir()}/install.db";
		return new InstallDatabase(new Database(new \Sqlite3($path)));
	}

	public function initReentrant(): ?string
	{
		if (!$this->db->queryValue('table-exists', 'installed_apps'))
		{
			if (!$this->db->exec('install/init'))
				return 'failed to initialize InstallDatabase';
		}

		return null;
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
