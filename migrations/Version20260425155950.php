<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425155950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Index update on ext_translation table by gedmo/doctrine-extensions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX general_translations_lookup_idx ON ext_translations');
        $this->addSql('DROP INDEX translations_lookup_idx ON ext_translations');
        $this->addSql('DROP INDEX lookup_unique_idx ON ext_translations');
        $this->addSql('CREATE UNIQUE INDEX lookup_unique_idx ON ext_translations (foreign_key, locale, object_class, field)');
        $this->addSql('OPTIMIZE TABLE ext_translations');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX lookup_unique_idx ON ext_translations');
        $this->addSql('CREATE INDEX general_translations_lookup_idx ON ext_translations (object_class, foreign_key)');
        $this->addSql('CREATE INDEX translations_lookup_idx ON ext_translations (locale, object_class, foreign_key)');
        $this->addSql('CREATE UNIQUE INDEX lookup_unique_idx ON ext_translations (locale, object_class, field, foreign_key)');
    }
}
