<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260806150021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change encrypted fields bundle';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_organization ADD tax_id_lookup VARCHAR(64) DEFAULT NULL, CHANGE tax_id tax_id LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_41221F7EFA120EC2 ON user_organization (tax_id_lookup)');
        $this->addSql('ALTER TABLE user_person ADD tax_id_lookup VARCHAR(64) DEFAULT NULL, CHANGE tax_id tax_id LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_518ECA4BFA120EC2 ON user_person (tax_id_lookup)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_518ECA4BFA120EC2 ON user_person');
        $this->addSql('ALTER TABLE user_person DROP tax_id_lookup, CHANGE tax_id tax_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_41221F7EFA120EC2 ON user_organization');
        $this->addSql('ALTER TABLE user_organization DROP tax_id_lookup, CHANGE tax_id tax_id VARCHAR(255) DEFAULT NULL');
    }
}
