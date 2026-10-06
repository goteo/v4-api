<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001120621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add address table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE address (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, line1 VARCHAR(255) NOT NULL, line2 VARCHAR(255) DEFAULT NULL, city VARCHAR(255) NOT NULL, post_code VARCHAR(255) NOT NULL, country VARCHAR(2) NOT NULL, INDEX IDX_D4E6F81A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F81A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE project_reward_claim ADD address_id INT DEFAULT NULL, DROP shipping_address_first_name, DROP shipping_address_last_name, DROP shipping_address_address_line1, DROP shipping_address_address_line2, DROP shipping_address_city, DROP shipping_address_post_code, DROP shipping_address_country');
        $this->addSql('ALTER TABLE project_reward_claim ADD CONSTRAINT FK_EA126118F5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id)');
        $this->addSql('CREATE INDEX IDX_EA126118F5B7AF75 ON project_reward_claim (address_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_reward_claim DROP FOREIGN KEY FK_EA126118F5B7AF75');
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F81A76ED395');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP INDEX IDX_EA126118F5B7AF75 ON project_reward_claim');
        $this->addSql('ALTER TABLE project_reward_claim ADD shipping_address_first_name VARCHAR(255) DEFAULT NULL, ADD shipping_address_last_name VARCHAR(255) DEFAULT NULL, ADD shipping_address_address_line1 VARCHAR(255) DEFAULT NULL, ADD shipping_address_address_line2 VARCHAR(255) DEFAULT NULL, ADD shipping_address_city VARCHAR(255) DEFAULT NULL, ADD shipping_address_post_code VARCHAR(255) DEFAULT NULL, ADD shipping_address_country VARCHAR(2) DEFAULT NULL, DROP address_id');
    }
}
