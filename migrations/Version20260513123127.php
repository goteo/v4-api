<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513123127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds localizable names to Categories';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category ADD name VARCHAR(255) NOT NULL, ADD locales JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category DROP name, DROP locales');
    }
}
