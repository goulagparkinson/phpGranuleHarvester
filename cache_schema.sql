CREATE TABLE "file" (
    "name" TEXT,
    "path" TEXT,
    "size" INTEGER,
    "sha1_name" TEXT,
    "sha1_path" TEXT,
    "creation_datetime" TEXT,
    "last_modification_datetime" TEXT,
    "product_id" TEXT,
    "start_datetime" TEXT,
    "stop_datetime" TEXT
);
CREATE INDEX "name" on file (name ASC);
CREATE INDEX "path" on file (path ASC);
CREATE INDEX "sha1_name" on file (sha1_name ASC);
CREATE INDEX "sha1_path" on file (sha1_path ASC);
CREATE INDEX "start_datetime" on file (start_datetime ASC);
CREATE INDEX "stop_datetime" on file (stop_datetime ASC);
