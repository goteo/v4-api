<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260710133732 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change ext_transalations.foreign_key from VARCHAR to INT to optimize localization joins';
    }

    public function up(Schema $schema): void
    {
        $count = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM ext_translations WHERE foreign_key NOT REGEXP '^[0-9]+$'"
        );

        $this->abortIf(
            $count > 0,
            'Non-numeric values found in ext_translations.foreign_key.'
        );

        $this->addSql('ALTER TABLE ext_translations MODIFY foreign_key INT NOT NULL');
        $this->addSql('OPTIMIZE TABLE ext_translations');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ext_translations MODIFY foreign_key VARCHAR(64) NOT NULL');
    }
}
