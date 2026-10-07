<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007151621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add theme table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE theme (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(56) NOT NULL, name VARCHAR(255) NOT NULL, locales JSON NOT NULL COMMENT \'(DC2Type:json)\', UNIQUE INDEX UNIQ_9775E708989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE theme');
    }
}
