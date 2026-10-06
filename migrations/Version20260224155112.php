<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224155112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removed UserToken entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_token DROP FOREIGN KEY FK_BDF55A637E3C61F9');
        $this->addSql('DROP TABLE user_token');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_token (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, token VARCHAR(68) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, date_created DATETIME NOT NULL, INDEX IDX_BDF55A637E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE user_token ADD CONSTRAINT FK_BDF55A637E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
    }
}
