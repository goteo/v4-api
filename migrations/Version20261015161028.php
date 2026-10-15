<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015161028 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audience (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, criteria JSON NOT NULL COMMENT \'(DC2Type:json)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE broadcast (id INT AUTO_INCREMENT NOT NULL, audience_id INT NOT NULL, sender VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, scheduled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(255) NOT NULL, date_created DATETIME NOT NULL, date_updated DATETIME NOT NULL, locales JSON NOT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_9BF17CF5848CC616 (audience_id), INDEX IDX_9BF17CF5CBC9BAD3 (), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dispatch (id INT AUTO_INCREMENT NOT NULL, broadcast_id INT NOT NULL, user_id INT NOT NULL, sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', error LONGTEXT DEFAULT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_8612665A9C7E80E0 (broadcast_id), INDEX IDX_8612665AA76ED395 (user_id), UNIQUE INDEX UNIQ_8612665A9C7E80E0A76ED395 (broadcast_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE broadcast ADD CONSTRAINT FK_9BF17CF5848CC616 FOREIGN KEY (audience_id) REFERENCES audience (id)');
        $this->addSql('ALTER TABLE dispatch ADD CONSTRAINT FK_8612665A9C7E80E0 FOREIGN KEY (broadcast_id) REFERENCES broadcast (id)');
        $this->addSql('ALTER TABLE dispatch ADD CONSTRAINT FK_8612665AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE broadcast DROP FOREIGN KEY FK_9BF17CF5848CC616');
        $this->addSql('ALTER TABLE dispatch DROP FOREIGN KEY FK_8612665A9C7E80E0');
        $this->addSql('ALTER TABLE dispatch DROP FOREIGN KEY FK_8612665AA76ED395');
        $this->addSql('DROP TABLE audience');
        $this->addSql('DROP TABLE broadcast');
        $this->addSql('DROP TABLE dispatch');
    }
}
