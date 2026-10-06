<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709122602 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change gateway_name to gateway_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_AF382D4E3D4E91C8BF396750 ON checkout');
        $this->addSql('ALTER TABLE checkout CHANGE gateway_name gateway_id VARCHAR(255) NOT NULL');
        $this->addSql('CREATE INDEX IDX_AF382D4EBF396750 ON checkout (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_AF382D4EBF396750 ON checkout');
        $this->addSql('ALTER TABLE checkout CHANGE gateway_id gateway_name VARCHAR(255) NOT NULL');
        $this->addSql('CREATE INDEX IDX_AF382D4E3D4E91C8BF396750 ON checkout (gateway_name, id)');
    }
}
