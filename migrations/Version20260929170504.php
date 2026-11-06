<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929170504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add to project_reward_claim a status and shipping address.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_reward_claim ADD status VARCHAR(255) NOT NULL, ADD shipping_address_first_name VARCHAR(255) NOT NULL, ADD shipping_address_last_name VARCHAR(255) NOT NULL, ADD shipping_address_address_line1 VARCHAR(255) NOT NULL, ADD shipping_address_address_line2 VARCHAR(255) DEFAULT NULL, ADD shipping_address_city VARCHAR(255) NOT NULL, ADD shipping_address_post_code VARCHAR(255) NOT NULL, ADD shipping_address_country VARCHAR(2) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_reward_claim DROP status, DROP shipping_address_first_name, DROP shipping_address_last_name, DROP shipping_address_address_line1, DROP shipping_address_address_line2, DROP shipping_address_city, DROP shipping_address_post_code, DROP shipping_address_country');
    }
}
