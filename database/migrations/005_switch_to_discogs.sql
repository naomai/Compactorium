-- no data loss, the table will be regenerated from barcodes
DROP TABLE copies;

ALTER TABLE albums
    RENAME COLUMN release_group_mbid TO slug;
ALTER TABLE albums
    RENAME COLUMN musicbrainz_json TO raw_json;

CREATE TABLE `copies` (
    id INTEGER PRIMARY KEY,
    library_id INTEGER NOT NULL,
    owner_id INTEGER NOT NULL,
    scan_id INTEGER NOT NULL,
    album_slug TEXT,
    created_at TEXT NOT NULL,

    FOREIGN KEY(library_id) REFERENCES libraries(id),
    FOREIGN KEY(scan_id) REFERENCES scans(id),
    FOREIGN KEY(album_slug) REFERENCES albums(slug)
);

UPDATE `scans` SET processed=FALSE;

DROP TABLE releases;