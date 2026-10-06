<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818090559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Property "cover" for project and project_rewards';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD cover LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE project_reward ADD cover LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_reward DROP cover');
        $this->addSql('ALTER TABLE project DROP cover');
    }
}
