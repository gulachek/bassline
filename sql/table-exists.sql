SELECT count(rowid)
FROM sqlite_master
WHERE type="table" AND name=?;
