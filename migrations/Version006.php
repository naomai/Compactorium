<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Converted from database/migrations/006_create_barcode_lookup_table.sql.
 */
final class Version006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create barcodes lookup table (formerly database/migrations/006_create_barcode_lookup_table.sql)';
    }

    public function up(Schema $schema): void
    {
        // Force slug regeneration.
        $this->addSql('DELETE FROM `albums`;');
        $this->addSql('CREATE TABLE `barcodes` (
            id INTEGER PRIMARY KEY,
            barcode INTEGER NOT NULL,
            album_slug TEXT NOT NULL,
            FOREIGN KEY(album_slug) REFERENCES albums(slug)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The original SQL migration system provided no rollbacks.');
    }
}