<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260729212816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "character" RENAME COLUMN health TO current_health');
        // Existing characters predate max_health; backfill it from their current
        // health before enforcing NOT NULL, rather than losing rows to a violation.
        $this->addSql('ALTER TABLE "character" ADD max_health INT DEFAULT NULL');
        $this->addSql('UPDATE "character" SET max_health = current_health');
        $this->addSql('ALTER TABLE "character" ALTER COLUMN max_health SET NOT NULL');
        $this->addSql('ALTER TABLE "character" ADD current_mana INT DEFAULT 10 NOT NULL');
        $this->addSql('ALTER TABLE "character" ADD max_mana INT DEFAULT 10 NOT NULL');
        $this->addSql('ALTER TABLE "character" ADD strength INT DEFAULT 10 NOT NULL');
        $this->addSql('ALTER TABLE "character" ADD dexterity INT DEFAULT 10 NOT NULL');
        $this->addSql('ALTER TABLE "character" ADD constitution INT DEFAULT 10 NOT NULL');
        $this->addSql('ALTER TABLE "character" ADD intelligence INT DEFAULT 10 NOT NULL');
        $this->addSql('ALTER TABLE "character" ADD wisdom INT DEFAULT 10 NOT NULL');
        $this->addSql('ALTER TABLE "character" ADD charisma INT DEFAULT 10 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE character ADD health INT NOT NULL');
        $this->addSql('ALTER TABLE character DROP current_health');
        $this->addSql('ALTER TABLE character DROP max_health');
        $this->addSql('ALTER TABLE character DROP current_mana');
        $this->addSql('ALTER TABLE character DROP max_mana');
        $this->addSql('ALTER TABLE character DROP strength');
        $this->addSql('ALTER TABLE character DROP dexterity');
        $this->addSql('ALTER TABLE character DROP constitution');
        $this->addSql('ALTER TABLE character DROP intelligence');
        $this->addSql('ALTER TABLE character DROP wisdom');
        $this->addSql('ALTER TABLE character DROP charisma');
    }
}
