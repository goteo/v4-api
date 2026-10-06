<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812124026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make territory_address LONGTEXT';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_call CHANGE territory_address territory_address LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE project CHANGE territory_address territory_address LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE territory_address territory_address LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user CHANGE territory_address territory_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE project CHANGE territory_address territory_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ext_translations CHANGE foreign_key foreign_key INT NOT NULL');
    }
}
