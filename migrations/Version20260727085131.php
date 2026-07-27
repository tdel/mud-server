<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260727085131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE account (id UUID NOT NULL, login VARCHAR(255) NOT NULL, current_character_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_7D3656A4EE8700AE ON account (current_character_id)');
        $this->addSql('CREATE TABLE character (id UUID NOT NULL, name VARCHAR(255) NOT NULL, health INT NOT NULL, account_id UUID DEFAULT NULL, current_room_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_937AB0349B6B5FBA ON character (account_id)');
        $this->addSql('CREATE INDEX IDX_937AB034FE1AF516 ON character (current_room_id)');
        $this->addSql('CREATE TABLE item (id UUID NOT NULL, template_id UUID NOT NULL, room_id UUID DEFAULT NULL, character_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_1F1B251E5DA0FB8 ON item (template_id)');
        $this->addSql('CREATE INDEX IDX_1F1B251E54177093 ON item (room_id)');
        $this->addSql('CREATE INDEX IDX_1F1B251E1136BE75 ON item (character_id)');
        $this->addSql('CREATE TABLE item_template (id UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, type VARCHAR(50) NOT NULL, weight INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FB6DD7485E237E06 ON item_template (name)');
        $this->addSql('CREATE TABLE room (id UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT NOT NULL, is_starting_room BOOLEAN DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_room_starting ON room (is_starting_room)');
        $this->addSql('CREATE TABLE room_exit (id UUID NOT NULL, direction VARCHAR(255) NOT NULL, source_room_id UUID DEFAULT NULL, target_room_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_F70D6E6A1DC8C8FB ON room_exit (source_room_id)');
        $this->addSql('CREATE INDEX IDX_F70D6E6A9F7FC9F8 ON room_exit (target_room_id)');
        $this->addSql('ALTER TABLE account ADD CONSTRAINT FK_7D3656A4EE8700AE FOREIGN KEY (current_character_id) REFERENCES character (id)');
        $this->addSql('ALTER TABLE character ADD CONSTRAINT FK_937AB0349B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE character ADD CONSTRAINT FK_937AB034FE1AF516 FOREIGN KEY (current_room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251E5DA0FB8 FOREIGN KEY (template_id) REFERENCES item_template (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251E54177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251E1136BE75 FOREIGN KEY (character_id) REFERENCES character (id)');
        $this->addSql('ALTER TABLE room_exit ADD CONSTRAINT FK_F70D6E6A1DC8C8FB FOREIGN KEY (source_room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE room_exit ADD CONSTRAINT FK_F70D6E6A9F7FC9F8 FOREIGN KEY (target_room_id) REFERENCES room (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account DROP CONSTRAINT FK_7D3656A4EE8700AE');
        $this->addSql('ALTER TABLE character DROP CONSTRAINT FK_937AB0349B6B5FBA');
        $this->addSql('ALTER TABLE character DROP CONSTRAINT FK_937AB034FE1AF516');
        $this->addSql('ALTER TABLE item DROP CONSTRAINT FK_1F1B251E5DA0FB8');
        $this->addSql('ALTER TABLE item DROP CONSTRAINT FK_1F1B251E54177093');
        $this->addSql('ALTER TABLE item DROP CONSTRAINT FK_1F1B251E1136BE75');
        $this->addSql('ALTER TABLE room_exit DROP CONSTRAINT FK_F70D6E6A1DC8C8FB');
        $this->addSql('ALTER TABLE room_exit DROP CONSTRAINT FK_F70D6E6A9F7FC9F8');
        $this->addSql('DROP TABLE account');
        $this->addSql('DROP TABLE character');
        $this->addSql('DROP TABLE item');
        $this->addSql('DROP TABLE item_template');
        $this->addSql('DROP TABLE room');
        $this->addSql('DROP TABLE room_exit');
    }
}
