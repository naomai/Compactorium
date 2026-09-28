<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Converted from database/migrations/007_add_album_front_cover_field.sql.
 */
final class Version007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add front cover image column to albums (formerly database/migrations/007_add_album_front_cover_field.sql)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `albums` ADD `image` TEXT;');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The original SQL migration system provided no rollbacks.');
    }
}