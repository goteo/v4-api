<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261015093422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add User, Person, Organization and Territory profile fields';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_category (user_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_E6C1FDC1A76ED395 (user_id), INDEX IDX_E6C1FDC112469DE2 (category_id), PRIMARY KEY(user_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_category ADD CONSTRAINT FK_E6C1FDC1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_category ADD CONSTRAINT FK_E6C1FDC112469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE match_call ADD territory_postal_code VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD territory_postal_code VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD share_location TINYINT(1) DEFAULT 0 NOT NULL, ADD support_anonymously TINYINT(1) DEFAULT 0 NOT NULL, ADD referral_source VARCHAR(255) DEFAULT NULL, ADD territory_postal_code VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user_organization ADD tax_id_type VARCHAR(255) DEFAULT NULL, ADD legal_form VARCHAR(255) DEFAULT NULL, ADD public_entity TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user_person ADD tax_id_type VARCHAR(255) DEFAULT NULL, ADD birth_year INT DEFAULT NULL, ADD gender VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_category DROP FOREIGN KEY FK_E6C1FDC1A76ED395');
        $this->addSql('ALTER TABLE user_category DROP FOREIGN KEY FK_E6C1FDC112469DE2');
        $this->addSql('DROP TABLE user_category');
        $this->addSql('ALTER TABLE project DROP territory_postal_code');
        $this->addSql('ALTER TABLE match_call DROP territory_postal_code');
        $this->addSql('ALTER TABLE user_person DROP tax_id_type, DROP birth_year, DROP gender');
        $this->addSql('ALTER TABLE user DROP share_location, DROP support_anonymously, DROP referral_source, DROP territory_postal_code');
        $this->addSql('ALTER TABLE user_organization DROP tax_id_type, DROP legal_form, DROP public_entity');
    }
}
