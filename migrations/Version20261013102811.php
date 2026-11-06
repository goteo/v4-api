<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261013102811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds has_shipping column to project_reward table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_reward ADD has_shipping TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_reward DROP has_shipping');
    }
}
