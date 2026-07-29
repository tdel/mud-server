<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260729115748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Existing dev-environment accounts predate the password field, so
        // backfill with a default (an unusable hash — those accounts simply
        // can't log in until re-registered) before dropping the default,
        // rather than losing rows to a NOT NULL violation.
        $this->addSql("ALTER TABLE account ADD password VARCHAR(255) DEFAULT '' NOT NULL");
        $this->addSql('ALTER TABLE account ALTER COLUMN password DROP DEFAULT');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7D3656A4AA08CB10 ON account (login)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_7D3656A4AA08CB10');
        $this->addSql('ALTER TABLE account DROP password');
    }
}
