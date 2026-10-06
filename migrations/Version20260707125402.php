<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707125402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enrich territories with address field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_call ADD territory_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD territory_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD territory_address VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_call DROP territory_address');
        $this->addSql('ALTER TABLE user DROP territory_address');
        $this->addSql('ALTER TABLE project DROP territory_address');
    }
}
