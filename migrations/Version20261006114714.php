<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261006114714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add review, review_area and review_comment tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, reviewer_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, date_created DATETIME NOT NULL, date_updated DATETIME NOT NULL, INDEX IDX_794381C6166D1F9C (project_id), INDEX IDX_794381C670574616 (reviewer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE review_area (id INT AUTO_INCREMENT NOT NULL, review_id INT NOT NULL, title VARCHAR(255) NOT NULL, summary LONGTEXT DEFAULT NULL, risk VARCHAR(255) NOT NULL, date_created DATETIME NOT NULL, date_updated DATETIME NOT NULL, INDEX IDX_352070703E2E969B (review_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE review_comment (id INT AUTO_INCREMENT NOT NULL, author_id INT DEFAULT NULL, area_id INT NOT NULL, body LONGTEXT NOT NULL, date_created DATETIME NOT NULL, date_updated DATETIME NOT NULL, INDEX IDX_F9AE69BF675F31B (author_id), INDEX IDX_F9AE69BBD0F409C (area_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C670574616 FOREIGN KEY (reviewer_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE review_area ADD CONSTRAINT FK_352070703E2E969B FOREIGN KEY (review_id) REFERENCES review (id)');
        $this->addSql('ALTER TABLE review_comment ADD CONSTRAINT FK_F9AE69BF675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE review_comment ADD CONSTRAINT FK_F9AE69BBD0F409C FOREIGN KEY (area_id) REFERENCES review_area (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6166D1F9C');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C670574616');
        $this->addSql('ALTER TABLE review_area DROP FOREIGN KEY FK_352070703E2E969B');
        $this->addSql('ALTER TABLE review_comment DROP FOREIGN KEY FK_F9AE69BF675F31B');
        $this->addSql('ALTER TABLE review_comment DROP FOREIGN KEY FK_F9AE69BBD0F409C');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE review_area');
        $this->addSql('DROP TABLE review_comment');
    }
}
