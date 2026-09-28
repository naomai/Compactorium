<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Converted from database/migrations/003_add_scan_processed.sql.
 */
final class Version003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add processed flag to scans (formerly database/migrations/003_add_scan_processed.sql)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `scans` ADD `processed` BOOL DEFAULT FALSE;');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The original SQL migration system provided no rollbacks.');
    }
}