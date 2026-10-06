<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522111940 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add matchfunding bool property to ProjectSupport';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_support ADD matchfunding TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_support DROP matchfunding');
    }
}
