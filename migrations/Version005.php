<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Converted from database/migrations/005_switch_to_discogs.sql.
 */
final class Version005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Switch album metadata source from MusicBrainz to Discogs (formerly database/migrations/005_switch_to_discogs.sql)';
    }

    public function up(Schema $schema): void
    {
        // No data loss, the table will be regenerated from barcodes.
        $this->addSql('DROP TABLE copies;');
        $this->addSql('ALTER TABLE albums
            RENAME COLUMN release_group_mbid TO slug;');
        $this->addSql('ALTER TABLE albums
            RENAME COLUMN musicbrainz_json TO raw_json;');
        $this->addSql('CREATE TABLE `copies` (
            id INTEGER PRIMARY KEY,
            library_id INTEGER NOT NULL,
            owner_id INTEGER NOT NULL,
            scan_id INTEGER NOT NULL,
            album_slug TEXT,
            created_at TEXT NOT NULL,

            FOREIGN KEY(library_id) REFERENCES libraries(id),
            FOREIGN KEY(scan_id) REFERENCES scans(id),
            FOREIGN KEY(album_slug) REFERENCES albums(slug)
        );');
        $this->addSql('UPDATE `scans` SET processed=FALSE;');
        $this->addSql('DROP TABLE releases;');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The original SQL migration system provided no rollbacks.');
    }
}