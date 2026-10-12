<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012162529 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds tables for mailing audience, batch and batch_recipient';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audience (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, criteria JSON NOT NULL COMMENT \'(DC2Type:json)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE batch (id INT AUTO_INCREMENT NOT NULL, audience_id INT NOT NULL, sender VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, scheduled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(255) NOT NULL, date_created DATETIME NOT NULL, date_updated DATETIME NOT NULL, locales JSON NOT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_F80B52D4848CC616 (audience_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE batch_recipient (id INT AUTO_INCREMENT NOT NULL, batch_id INT NOT NULL, user_id INT NOT NULL, sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', error LONGTEXT DEFAULT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_253D747FF39EBE7A (batch_id), INDEX IDX_253D747FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE batch ADD CONSTRAINT FK_F80B52D4848CC616 FOREIGN KEY (audience_id) REFERENCES audience (id)');
        $this->addSql('ALTER TABLE batch_recipient ADD CONSTRAINT FK_253D747FF39EBE7A FOREIGN KEY (batch_id) REFERENCES batch (id)');
        $this->addSql('ALTER TABLE batch_recipient ADD CONSTRAINT FK_253D747FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE batch DROP FOREIGN KEY FK_F80B52D4848CC616');
        $this->addSql('ALTER TABLE batch_recipient DROP FOREIGN KEY FK_253D747FF39EBE7A');
        $this->addSql('ALTER TABLE batch_recipient DROP FOREIGN KEY FK_253D747FA76ED395');
        $this->addSql('DROP TABLE audience');
        $this->addSql('DROP TABLE batch');
        $this->addSql('DROP TABLE batch_recipient');
    }
}
