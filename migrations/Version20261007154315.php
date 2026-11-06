<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007154315 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add theme tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE project_theme (project_id INT NOT NULL, theme_id INT NOT NULL, INDEX IDX_8420EE96166D1F9C (project_id), INDEX IDX_8420EE9659027487 (theme_id), PRIMARY KEY(project_id, theme_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE theme (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(56) NOT NULL, name VARCHAR(255) NOT NULL, locales JSON NOT NULL COMMENT \'(DC2Type:json)\', UNIQUE INDEX UNIQ_9775E708989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_theme ADD CONSTRAINT FK_8420EE96166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_theme ADD CONSTRAINT FK_8420EE9659027487 FOREIGN KEY (theme_id) REFERENCES theme (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_theme DROP FOREIGN KEY FK_8420EE96166D1F9C');
        $this->addSql('ALTER TABLE project_theme DROP FOREIGN KEY FK_8420EE9659027487');
        $this->addSql('DROP TABLE project_theme');
        $this->addSql('DROP TABLE theme');
    }
}
