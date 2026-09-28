<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Replace delight-im/auth tables with Symfony security's user table.
 * Remove owner_id integer stubs and add proper foreign keys to users.
 */
final class Version010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace delight-im/auth with Symfony security user table and add owner FKs';
    }

    /**
     * The original migration 008 used PRAGMA foreign_keys = OFF, so this
     * migration must run outside a transaction to match that behavior.
     */
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('PRAGMA foreign_keys = OFF;');

        // Drop all delight-im/auth tables
        $this->addSql('DROP TABLE IF EXISTS users_throttling;');
        $this->addSql('DROP TABLE IF EXISTS users_resets;');
        $this->addSql('DROP TABLE IF EXISTS users_remembered;');
        $this->addSql('DROP TABLE IF EXISTS users_otps;');
        $this->addSql('DROP TABLE IF EXISTS users_confirmations;');
        $this->addSql('DROP TABLE IF EXISTS users_audit_log;');
        $this->addSql('DROP TABLE IF EXISTS users_2fa;');
        $this->addSql('DROP TABLE IF EXISTS users;');

        // Create the Symfony security user table
        $this->addSql('CREATE TABLE "users" (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            "email" TEXT NOT NULL COLLATE NOCASE CHECK(LENGTH("email") <= 180),
            "username" TEXT NOT NULL COLLATE NOCASE CHECK(LENGTH("username") <= 180),
            "password" TEXT NOT NULL COLLATE BINARY CHECK(LENGTH("password") <= 255),
            "roles" TEXT NOT NULL DEFAULT \'[]\',
            "registered_at" TEXT NOT NULL,
            "last_login_at" TEXT DEFAULT NULL,
            "verified" INTEGER NOT NULL DEFAULT 0,
            "verification_token" TEXT DEFAULT NULL,
            CONSTRAINT "users_email_uq" UNIQUE ("email"),
            CONSTRAINT "users_username_uq" UNIQUE ("username")
        );');

        // Seed the admin user (id=0) so existing owner_id=0 records remain valid
        $this->addSql('INSERT INTO "users" (id, email, username, password, roles, registered_at, verified)
            VALUES (0, \'admin@localhost\', \'admin\', \'\', \'["ROLE_ADMIN"]\', datetime(\'now\'), 1);');

        // libraries: rebuild with FK (owner_id NOT NULL, default 0)
        $this->addSql('CREATE TABLE "libraries_new" (
            "id" INTEGER PRIMARY KEY NOT NULL,
            "owner_id" INTEGER NOT NULL DEFAULT 0,
            "name" TEXT NOT NULL,
            "description" TEXT DEFAULT NULL,
            FOREIGN KEY("owner_id") REFERENCES "users"("id")
        );');
        $this->addSql('INSERT INTO "libraries_new" SELECT * FROM "libraries";');
        $this->addSql('DROP TABLE "libraries";');
        $this->addSql('ALTER TABLE "libraries_new" RENAME TO "libraries";');

        // scans: rebuild with FK (owner_id NOT NULL)
        $this->addSql('CREATE TABLE "scans_new" (
            "id" INTEGER PRIMARY KEY NOT NULL,
            "owner_id" INTEGER NOT NULL DEFAULT 0,
            "library_id" INTEGER NOT NULL,
            "barcode" TEXT NOT NULL,
            "scanned_at" TEXT NOT NULL,
            "processed" INTEGER DEFAULT 0 NOT NULL,
            FOREIGN KEY("library_id") REFERENCES "libraries"("id"),
            FOREIGN KEY("owner_id") REFERENCES "users"("id")
        );');
        $this->addSql('INSERT INTO "scans_new" SELECT * FROM "scans";');
        $this->addSql('DROP TABLE "scans";');
        $this->addSql('ALTER TABLE "scans_new" RENAME TO "scans";');
        $this->addSql('CREATE UNIQUE INDEX idx_scans_unique ON scans (barcode, library_id);');

        // copies: rebuild with FK (owner_id NOT NULL)
        $this->addSql('CREATE TABLE "copies_new" (
            "id" INTEGER PRIMARY KEY NOT NULL,
            "library_id" INTEGER NOT NULL,
            "owner_id" INTEGER NOT NULL DEFAULT 0,
            "scan_id" INTEGER DEFAULT NULL,
            "album_slug" TEXT DEFAULT NULL,
            "created_at" TEXT NOT NULL,
            FOREIGN KEY("library_id") REFERENCES "libraries"("id"),
            FOREIGN KEY("scan_id") REFERENCES "scans"("id"),
            FOREIGN KEY("album_slug") REFERENCES "albums"("slug"),
            FOREIGN KEY("owner_id") REFERENCES "users"("id")
        );');
        $this->addSql('INSERT INTO "copies_new" SELECT * FROM "copies";');
        $this->addSql('DROP TABLE "copies";');
        $this->addSql('ALTER TABLE "copies_new" RENAME TO "copies";');

        $this->addSql('PRAGMA foreign_keys = ON;');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Migration from delight-im to Symfony security is irreversible.');
    }
}