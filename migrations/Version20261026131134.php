<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261026131134 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add default flag to addresses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE address ADD is_default TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE address DROP is_default');
    }
}
