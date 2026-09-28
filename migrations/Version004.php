<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Converted from database/migrations/004_alter_copy_mbid_nullable_for_disambig.sql.
 */
final class Version004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make copies.release_mbid nullable for disambiguation (formerly database/migrations/004_alter_copy_mbid_nullable_for_disambig.sql)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `copies`
            ALTER COLUMN release_mbid DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The original SQL migration system provided no rollbacks.');
    }
}