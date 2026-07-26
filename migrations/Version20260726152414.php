<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260726152414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE account (id BINARY(16) NOT NULL, login VARCHAR(255) NOT NULL, current_character_id BINARY(16) DEFAULT NULL, INDEX IDX_7D3656A4EE8700AE (current_character_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE `character` (id BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, health INT NOT NULL, account_id BINARY(16) DEFAULT NULL, current_room_id BINARY(16) DEFAULT NULL, INDEX IDX_937AB0349B6B5FBA (account_id), INDEX IDX_937AB034FE1AF516 (current_room_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE item (id BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(50) NOT NULL, room_id BINARY(16) DEFAULT NULL, character_id BINARY(16) DEFAULT NULL, INDEX IDX_1F1B251E54177093 (room_id), INDEX IDX_1F1B251E1136BE75 (character_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE room (id BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, is_starting_room TINYINT DEFAULT NULL, UNIQUE INDEX uniq_room_starting (is_starting_room), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE room_exit (id BINARY(16) NOT NULL, direction VARCHAR(255) NOT NULL, source_room_id BINARY(16) DEFAULT NULL, target_room_id BINARY(16) DEFAULT NULL, INDEX IDX_F70D6E6A1DC8C8FB (source_room_id), INDEX IDX_F70D6E6A9F7FC9F8 (target_room_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('ALTER TABLE account ADD CONSTRAINT FK_7D3656A4EE8700AE FOREIGN KEY (current_character_id) REFERENCES `character` (id)');
        $this->addSql('ALTER TABLE `character` ADD CONSTRAINT FK_937AB0349B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE `character` ADD CONSTRAINT FK_937AB034FE1AF516 FOREIGN KEY (current_room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251E54177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251E1136BE75 FOREIGN KEY (character_id) REFERENCES `character` (id)');
        $this->addSql('ALTER TABLE room_exit ADD CONSTRAINT FK_F70D6E6A1DC8C8FB FOREIGN KEY (source_room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE room_exit ADD CONSTRAINT FK_F70D6E6A9F7FC9F8 FOREIGN KEY (target_room_id) REFERENCES room (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account DROP FOREIGN KEY FK_7D3656A4EE8700AE');
        $this->addSql('ALTER TABLE `character` DROP FOREIGN KEY FK_937AB0349B6B5FBA');
        $this->addSql('ALTER TABLE `character` DROP FOREIGN KEY FK_937AB034FE1AF516');
        $this->addSql('ALTER TABLE item DROP FOREIGN KEY FK_1F1B251E54177093');
        $this->addSql('ALTER TABLE item DROP FOREIGN KEY FK_1F1B251E1136BE75');
        $this->addSql('ALTER TABLE room_exit DROP FOREIGN KEY FK_F70D6E6A1DC8C8FB');
        $this->addSql('ALTER TABLE room_exit DROP FOREIGN KEY FK_F70D6E6A9F7FC9F8');
        $this->addSql('DROP TABLE account');
        $this->addSql('DROP TABLE `character`');
        $this->addSql('DROP TABLE item');
        $this->addSql('DROP TABLE room');
        $this->addSql('DROP TABLE room_exit');
    }
}
