-- CREATE TEMP TABLE source_code_apps (app TEXT, semver TEXT);
-- CREATE TEMP TABLE install_errors (app TEXT, err TEXT);
INSERT OR REPLACE INTO installed_apps(app, semver)
	SELECT src.app, src.semver, err.err FROM source_code_apps AS src
	LEFT JOIN install_errors AS err ON src.app = err.app
	WHERE err.err IS NULL
	;
