<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261126094753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add denormalized budget to projects';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project ADD budget_minimum_money_amount INT DEFAULT NULL, ADD budget_minimum_money_currency VARCHAR(255) DEFAULT NULL, ADD budget_minimum_money_conversion JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD budget_minimum_task_amount INT DEFAULT NULL, ADD budget_minimum_task_currency VARCHAR(255) DEFAULT NULL, ADD budget_minimum_task_conversion JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD budget_minimum_material_amount INT DEFAULT NULL, ADD budget_minimum_material_currency VARCHAR(255) DEFAULT NULL, ADD budget_minimum_material_conversion JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD budget_minimum_infra_amount INT DEFAULT NULL, ADD budget_minimum_infra_currency VARCHAR(255) DEFAULT NULL, ADD budget_minimum_infra_conversion JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD budget_optimum_money_amount INT DEFAULT NULL, ADD budget_optimum_money_currency VARCHAR(255) DEFAULT NULL, ADD budget_optimum_money_conversion JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD budget_optimum_task_amount INT DEFAULT NULL, ADD budget_optimum_task_currency VARCHAR(255) DEFAULT NULL, ADD budget_optimum_task_conversion JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD budget_optimum_material_amount INT DEFAULT NULL, ADD budget_optimum_material_currency VARCHAR(255) DEFAULT NULL, ADD budget_optimum_material_conversion JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD budget_optimum_infra_amount INT DEFAULT NULL, ADD budget_optimum_infra_currency VARCHAR(255) DEFAULT NULL, ADD budget_optimum_infra_conversion JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project DROP budget_minimum_money_amount, DROP budget_minimum_money_currency, DROP budget_minimum_money_conversion, DROP budget_minimum_task_amount, DROP budget_minimum_task_currency, DROP budget_minimum_task_conversion, DROP budget_minimum_material_amount, DROP budget_minimum_material_currency, DROP budget_minimum_material_conversion, DROP budget_minimum_infra_amount, DROP budget_minimum_infra_currency, DROP budget_minimum_infra_conversion, DROP budget_optimum_money_amount, DROP budget_optimum_money_currency, DROP budget_optimum_money_conversion, DROP budget_optimum_task_amount, DROP budget_optimum_task_currency, DROP budget_optimum_task_conversion, DROP budget_optimum_material_amount, DROP budget_optimum_material_currency, DROP budget_optimum_material_conversion, DROP budget_optimum_infra_amount, DROP budget_optimum_infra_currency, DROP budget_optimum_infra_conversion');
    }
}
