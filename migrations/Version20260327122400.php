<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260327122400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS app_user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, password_hash VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_88BDF3E9F85E0677 (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE `admin` CHANGE username username VARCHAR(180) NOT NULL, CHANGE password_hash password_hash VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE events CHANGE title title VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE date date DATETIME NOT NULL, CHANGE location location VARCHAR(255) NOT NULL, CHANGE seats seats INT NOT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE reservations CHANGE name name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE phone phone VARCHAR(30) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE IF EXISTS app_user');
        $this->addSql('DROP TABLE IF EXISTS messenger_messages');
        $this->addSql('ALTER TABLE `admin` CHANGE username username VARCHAR(100) NOT NULL, CHANGE password_hash password_hash VARCHAR(200) NOT NULL');
        $this->addSql('ALTER TABLE events CHANGE title title VARCHAR(200) NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE date date DATE NOT NULL, CHANGE location location VARCHAR(200) DEFAULT \'NULL\', CHANGE seats seats INT DEFAULT NULL, CHANGE image image VARCHAR(500) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE reservations CHANGE name name VARCHAR(150) NOT NULL, CHANGE email email VARCHAR(200) NOT NULL, CHANGE phone phone VARCHAR(20) DEFAULT \'NULL\', CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
    }
}
