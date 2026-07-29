<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260729222316 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Existing characters predate the race system and have no meaningful
        // race to assign; start from a clean slate rather than picking one
        // arbitrarily (explicit request: wipe existing characters for a
        // clean game).
        $this->addSql('DELETE FROM item WHERE character_id IS NOT NULL');
        $this->addSql('UPDATE account SET current_character_id = NULL');
        $this->addSql('DELETE FROM "character"');
        $this->addSql('ALTER TABLE "character" ADD race VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE character DROP race');
    }
}
