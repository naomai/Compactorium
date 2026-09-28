<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Converted from database/migrations/009_copy_to_scan_optional.sql.
 */
final class Version009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make copies.scan_id optional (formerly database/migrations/009_copy_to_scan_optional.sql)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `copies`
            ALTER COLUMN scan_id DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The original SQL migration system provided no rollbacks.');
    }
}