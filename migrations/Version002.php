<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Converted from database/migrations/002_create_scans_unique_idx.sql.
 */
final class Version002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique index on scans (barcode, library_id) (formerly database/migrations/002_create_scans_unique_idx.sql)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX idx_scans_unique
            ON scans (barcode, library_id);');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The original SQL migration system provided no rollbacks.');
    }
}