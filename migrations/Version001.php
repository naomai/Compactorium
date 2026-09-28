<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Converted from database/migrations/001_create_solo_library.sql.
 */
final class Version001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Insert the default solo library (formerly database/migrations/001_create_solo_library.sql)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO `libraries`
            (id, owner_id, name, description)
            VALUES
            (0, 0, "Default library", "")');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The original SQL migration system provided no rollbacks.');
    }
}