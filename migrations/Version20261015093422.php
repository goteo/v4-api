<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015093422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add User, Person and Organization profile fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_category (user_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_E6C1FDC1A76ED395 (user_id), INDEX IDX_E6C1FDC112469DE2 (category_id), PRIMARY KEY(user_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_category ADD CONSTRAINT FK_E6C1FDC1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_category ADD CONSTRAINT FK_E6C1FDC112469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('CREATE TABLE user_theme (user_id INT NOT NULL, theme_id INT NOT NULL, INDEX IDX_75B71C50A76ED395 (user_id), INDEX IDX_75B71C5059027487 (theme_id), PRIMARY KEY(user_id, theme_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_theme ADD CONSTRAINT FK_75B71C50A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_theme ADD CONSTRAINT FK_75B71C5059027487 FOREIGN KEY (theme_id) REFERENCES theme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD share_location TINYINT(1) DEFAULT 0 NOT NULL, ADD is_anonymous TINYINT(1) DEFAULT 0 NOT NULL, ADD referral_source VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user_organization ADD tax_id_type VARCHAR(255) DEFAULT NULL, ADD legal_form VARCHAR(255) DEFAULT NULL, ADD public_entity TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user_person ADD tax_id_type VARCHAR(255) DEFAULT NULL, ADD birth_year INT DEFAULT NULL, ADD gender VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_category DROP FOREIGN KEY FK_E6C1FDC1A76ED395');
        $this->addSql('ALTER TABLE user_category DROP FOREIGN KEY FK_E6C1FDC112469DE2');
        $this->addSql('DROP TABLE user_category');
        $this->addSql('ALTER TABLE user_theme DROP FOREIGN KEY FK_75B71C50A76ED395');
        $this->addSql('ALTER TABLE user_theme DROP FOREIGN KEY FK_75B71C5059027487');
        $this->addSql('DROP TABLE user_theme');
        $this->addSql('ALTER TABLE user_person DROP tax_id_type, DROP birth_year, DROP gender');
        $this->addSql('ALTER TABLE user DROP share_location, DROP is_anonymous, DROP referral_source');
        $this->addSql('ALTER TABLE user_organization DROP tax_id_type, DROP legal_form, DROP public_entity');
    }
}
