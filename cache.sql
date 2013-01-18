BEGIN TRANSACTION;
CREATE TABLE "files" (
    "path" TEXT PRIMARY KEY,
    "name" TEXT,
    "size" INTEGER,
    "sha1_name" TEXT,
    "sha1_path" TEXT,
    "md5sum" TEXT,
    "create_datetime" TEXT,
    "update_datetime" TEXT,
    "delete_datetime" TEXT,
    "product_id" TEXT,
    "start_datetime" TEXT,
    "stop_datetime" TEXT
);

CREATE INDEX "name" on files (name ASC);
CREATE INDEX "product_id" on files (name ASC);
CREATE INDEX "sha1_name" on files (sha1_name ASC);
CREATE INDEX "sha1_path" on files (sha1_path ASC);
CREATE UNIQUE INDEX "md5sum" on files (sha1_path ASC);
CREATE INDEX "create_datetime" on files (create_datetime ASC);
CREATE INDEX "update_datetime" on files (update_datetime ASC);
CREATE INDEX "delete_datetime" on files (delete_datetime ASC);
CREATE INDEX "start_datetime" on files (start_datetime ASC);
CREATE INDEX "stop_datetime" on files (stop_datetime ASC);


CREATE TABLE files_log (
    "id" INTEGER PRIMARY KEY,
    "path" TEXT,
    "name" TEXT,
    "NEW_create_datetime" TEXT,
    "LAST_create_datetime" TEXT,
    "NEW_update_datetime" TEXT,
    "LAST_update_datetime" TEXT,
    "NEW_delete_datetime" TEXT,
    "LAST_delete_datetime" TEXTn
    "sqlAction" VARCHAR(15));
);


--  Create an update trigger
CREATE TRIGGER update_files_log AFTER UPDATE ON files
BEGIN
  INSERT INTO files_log (path, name,
    NEW_create_datetime, OLD_create_datetime,
    NEW_update_datetime, OLD_update_datetime,
    NEW_delete_datetime, OLD_delete_datetime,
    sqlAction, create_datetime, update_datetime)
  VALUES (old.path, old.name, new.create_datetime, old.create_datetime,
    new.update_datetime, old.update_datetime,
    new.delete_datetime, old.delete_datetime,
    'UPDATE', old.create_datetime, DATETIME('NOW') );

END;

--  Also create an insert trigger

CREATE TRIGGER insert_files_log AFTER INSERT ON files
BEGIN
INSERT INTO files_log (path, name,
    NEW_create_datetime,
    NEW_update_datetime,
    NEW_delete_datetime,
    sqlAction)
  VALUES (old.path, old.name,
    old.create_datetime,
    new.update_datetime,
    new.delete_datetime,
    'INSERT', DATETIME('NOW'), DATETIME('NOW') );

END;

--  Also create a DELETE trigger
CREATE TRIGGER delete_files_log DELETE ON files
BEGIN

INSERT INTO update_files_log(path, name, ekey,fnOLD,lnNEW,examOLD,scoreOLD,
                      sqlAction,timeEnter)

          values (old.ekey,old.fn,old.ln,old.exam,old.score,
                  'DELETE',DATETIME('NOW') );

END;


COMMIT;
