<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add passkey_credential table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS passkey_credential (id INT AUTO_INCREMENT NOT NULL, user_type VARCHAR(16) NOT NULL, user_identifier VARCHAR(180) NOT NULL, credential_id VARCHAR(255) NOT NULL, public_key LONGTEXT DEFAULT NULL, sign_count INT DEFAULT NULL, transports JSON DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_passkey_credential_id (credential_id), INDEX idx_passkey_user (user_type, user_identifier), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS passkey_credential');
    }
}
