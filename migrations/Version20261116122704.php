<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261116122704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add denormalized budget to projects';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project ADD budget_currency VARCHAR(255) DEFAULT NULL, ADD budget_minimum_money INT DEFAULT NULL, ADD budget_minimum_task INT DEFAULT NULL, ADD budget_minimum_material INT DEFAULT NULL, ADD budget_minimum_infra INT DEFAULT NULL, ADD budget_optimum_money INT DEFAULT NULL, ADD budget_optimum_task INT DEFAULT NULL, ADD budget_optimum_material INT DEFAULT NULL, ADD budget_optimum_infra INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project DROP budget_currency, DROP budget_minimum_money, DROP budget_minimum_task, DROP budget_minimum_material, DROP budget_minimum_infra, DROP budget_optimum_money, DROP budget_optimum_task, DROP budget_optimum_material, DROP budget_optimum_infra');
    }
}
