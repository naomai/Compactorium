-- force slug regeneration
DELETE FROM `albums`;

CREATE TABLE `barcodes` (
    id INTEGER PRIMARY KEY,
    barcode INTEGER NOT NULL,
    album_slug TEXT NOT NULL,
    FOREIGN KEY(album_slug) REFERENCES albums(slug)
)
