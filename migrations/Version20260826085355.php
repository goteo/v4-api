<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826085355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds project_collaboration_candidacy table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE project_collaboration_candidacy (id INT AUTO_INCREMENT NOT NULL, collaboration_id INT NOT NULL, user_id INT NOT NULL, description LONGTEXT NOT NULL, status VARCHAR(255) NOT NULL, date_created DATETIME NOT NULL, date_updated DATETIME NOT NULL, INDEX IDX_378F2D4AEF1544CE (collaboration_id), INDEX IDX_378F2D4AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_collaboration_candidacy ADD CONSTRAINT FK_378F2D4AEF1544CE FOREIGN KEY (collaboration_id) REFERENCES project_collaboration (id)');
        $this->addSql('ALTER TABLE project_collaboration_candidacy ADD CONSTRAINT FK_378F2D4AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_collaboration_candidacy DROP FOREIGN KEY FK_378F2D4AEF1544CE');
        $this->addSql('ALTER TABLE project_collaboration_candidacy DROP FOREIGN KEY FK_378F2D4AA76ED395');
        $this->addSql('DROP TABLE project_collaboration_candidacy');
    }
}
