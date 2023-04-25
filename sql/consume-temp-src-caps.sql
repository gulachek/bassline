-- CREATE TEMP TABLE src_caps(app T, name T)

DELETE FROM group_capabilities
WHERE cap_id IN (
	SELECT installed.id FROM capabilities AS installed
	LEFT JOIN src_caps AS src
	ON installed.app = src.app AND installed.name = src.name
	WHERE src.app IS NULL
)
;

INSERT OR IGNORE INTO capabilities (app, name)
SELECT app, name FROM src_caps;

DROP TABLE src_caps;
