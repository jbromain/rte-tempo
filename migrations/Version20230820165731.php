<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230820165731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__jour_tempo AS SELECT id, date_jour, code_jour, periode FROM jour_tempo');
        $this->addSql('DROP TABLE jour_tempo');
        $this->addSql('CREATE TABLE jour_tempo (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date_jour DATE NOT NULL --(DC2Type:date_immutable)
        , code_jour SMALLINT NOT NULL, periode VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO jour_tempo (id, date_jour, code_jour, periode) SELECT id, date_jour, code_jour, periode FROM __temp__jour_tempo');
        $this->addSql('DROP TABLE __temp__jour_tempo');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_769D4A1CFAC95031 ON jour_tempo (date_jour)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__jour_tempo AS SELECT id, date_jour, code_jour, periode FROM jour_tempo');
        $this->addSql('DROP TABLE jour_tempo');
        $this->addSql('CREATE TABLE jour_tempo (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date_jour DATE NOT NULL, code_jour SMALLINT NOT NULL, periode VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO jour_tempo (id, date_jour, code_jour, periode) SELECT id, date_jour, code_jour, periode FROM __temp__jour_tempo');
        $this->addSql('DROP TABLE __temp__jour_tempo');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_769D4A1CFAC95031 ON jour_tempo (date_jour)');
    }
}
