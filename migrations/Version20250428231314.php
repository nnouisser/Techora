<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250428231314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE personne ADD created_by_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE personne ADD CONSTRAINT FK_FCEC9EFB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FCEC9EFB03A8386 ON personne (created_by_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE personne DROP FOREIGN KEY FK_FCEC9EFB03A8386
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_FCEC9EFB03A8386 ON personne
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE personne DROP created_by_id
        SQL);
    }
}
