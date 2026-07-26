<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260726224325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Split Item into ItemTemplate (catalog) and Item (world instance referencing a template)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE item_template (id BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(50) NOT NULL, weight INT NOT NULL, UNIQUE INDEX UNIQ_FB6DD7485E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('ALTER TABLE item ADD template_id BINARY(16) NOT NULL, DROP name, DROP description, DROP type');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251E5DA0FB8 FOREIGN KEY (template_id) REFERENCES item_template (id)');
        $this->addSql('CREATE INDEX IDX_1F1B251E5DA0FB8 ON item (template_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE item_template');
        $this->addSql('ALTER TABLE item DROP FOREIGN KEY FK_1F1B251E5DA0FB8');
        $this->addSql('DROP INDEX IDX_1F1B251E5DA0FB8 ON item');
        $this->addSql('ALTER TABLE item ADD name VARCHAR(255) NOT NULL, ADD description LONGTEXT DEFAULT NULL, ADD type VARCHAR(50) NOT NULL, DROP template_id');
    }
}
