<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261126122914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add UN Sustainable Development Goals to projects';
    }

    public function up(Schema $schema): void
    {
        // Default only backfills existing rows: '' is not valid JSON
        $this->addSql('ALTER TABLE project ADD sdgs JSON DEFAULT \'[]\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE project ALTER sdgs DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP sdgs');
    }
}
