CREATE TABLE `libraries` (
    id INTEGER PRIMARY KEY,
    owner_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT
);

CREATE TABLE `scans` (
    id INTEGER PRIMARY KEY,
    owner_id INTEGER NOT NULL,
    library_id INTEGER NOT NULL,
    barcode TEXT NOT NULL,
    scanned_at TEXT NOT NULL,
    FOREIGN KEY(library_id) REFERENCES libraries(id)
);

CREATE TABLE `copies` (
    id INTEGER PRIMARY KEY,
    library_id INTEGER NOT NULL,
    owner_id INTEGER NOT NULL,
    scan_id INTEGER NOT NULL,
    release_mbid TEXT NOT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY(library_id) REFERENCES libraries(id),
    FOREIGN KEY(scan_id) REFERENCES scans(id),
    FOREIGN KEY(release_mbid) REFERENCES releases(release_mbid)
);

CREATE TABLE `releases` (
    release_mbid TEXT PRIMARY KEY,
    release_group_mbid TEXT NOT NULL,
    barcode TEXT NOT NULL,
    musicbrainz_json TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY(release_group_mbid) REFERENCES albums(release_group_mbid)
);

CREATE TABLE `albums` (
    release_group_mbid TEXT PRIMARY KEY,
    title TEXT NOT NULL, 
    artist TEXT NOT NULL, 
    year TEXT NOT NULL,
    musicbrainz_json TEXT,
    created_at TEXT NOT NULL 
);