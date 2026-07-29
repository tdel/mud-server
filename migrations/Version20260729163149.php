<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260729163149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item ADD slot VARCHAR(20) DEFAULT NULL');
        // Postgres treats NULL as distinct from NULL, so this plain UNIQUE constraint only
        // rejects two rows that both have a non-null character_id AND the same non-null slot -
        // items in a room (character_id NULL) or carried-but-unequipped (slot NULL) are exempt.
        // DEFERRABLE INITIALLY DEFERRED is required (and only possible on a non-partial constraint)
        // so that swapping an equipped item within one flush/transaction doesn't trip a transient
        // violation between the old item's UPDATE (slot -> NULL) and the new item's UPDATE (slot -> x).
        $this->addSql('ALTER TABLE item ADD CONSTRAINT uniq_character_slot UNIQUE (character_id, slot) DEFERRABLE INITIALLY DEFERRED');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item DROP CONSTRAINT uniq_character_slot');
        $this->addSql('ALTER TABLE item DROP slot');
    }
}
