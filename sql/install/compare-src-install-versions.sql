-- CREATE TEMP TABLE source_code_apps (app TEXT, semver TEXT);
SELECT 
	coalesce(installed.app, src.app) AS app,
	installed.semver AS installed_semver,
	src.semver AS src_semver
FROM installed_apps AS installed
FULL OUTER JOIN source_code_apps AS src
