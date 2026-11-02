<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261102111151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image to match_call';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_call ADD image LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_call DROP image');
    }
}
