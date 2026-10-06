<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522085831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add territory and description to Users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD description LONGTEXT DEFAULT NULL, ADD territory_country VARCHAR(255) DEFAULT NULL, ADD territory_sub_lvl1 VARCHAR(255) DEFAULT NULL, ADD territory_sub_lvl2 VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP description, DROP territory_country, DROP territory_sub_lvl1, DROP territory_sub_lvl2');
    }
}
