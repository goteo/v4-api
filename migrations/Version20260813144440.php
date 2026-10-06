<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813144440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Split description into: brief, about, goal and team';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD desc_about LONGTEXT DEFAULT NULL, ADD desc_goal LONGTEXT DEFAULT NULL, ADD desc_team LONGTEXT DEFAULT NULL, CHANGE description desc_brief LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD description LONGTEXT DEFAULT NULL, DROP desc_brief, DROP desc_about, DROP desc_goal, DROP desc_team');
    }
}
