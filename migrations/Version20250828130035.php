<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250828130035 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__tarification AS SELECT id, bleu_hc, bleu_hp, blanc_hc, blanc_hp, rouge_hc, rouge_hp, data_gouv_id, tarif_force, date_debut FROM tarification');
        $this->addSql('DROP TABLE tarification');
        $this->addSql('CREATE TABLE tarification (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, bleu_hc DOUBLE PRECISION NOT NULL, bleu_hp DOUBLE PRECISION NOT NULL, blanc_hc DOUBLE PRECISION NOT NULL, blanc_hp DOUBLE PRECISION NOT NULL, rouge_hc DOUBLE PRECISION NOT NULL, rouge_hp DOUBLE PRECISION NOT NULL, data_gouv_id INTEGER NOT NULL, tarif_force BOOLEAN DEFAULT 0 NOT NULL, date_debut VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO tarification (id, bleu_hc, bleu_hp, blanc_hc, blanc_hp, rouge_hc, rouge_hp, data_gouv_id, tarif_force, date_debut) SELECT id, bleu_hc, bleu_hp, blanc_hc, blanc_hp, rouge_hc, rouge_hp, data_gouv_id, tarif_force, date_debut FROM __temp__tarification');
        $this->addSql('DROP TABLE __temp__tarification');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__tarification AS SELECT id, bleu_hc, bleu_hp, blanc_hc, blanc_hp, rouge_hc, rouge_hp, data_gouv_id, tarif_force, date_debut FROM tarification');
        $this->addSql('DROP TABLE tarification');
        $this->addSql('CREATE TABLE tarification (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, bleu_hc DOUBLE PRECISION NOT NULL, bleu_hp DOUBLE PRECISION NOT NULL, blanc_hc DOUBLE PRECISION NOT NULL, blanc_hp DOUBLE PRECISION NOT NULL, rouge_hc DOUBLE PRECISION NOT NULL, rouge_hp DOUBLE PRECISION NOT NULL, data_gouv_id INTEGER NOT NULL, tarif_force BOOLEAN DEFAULT 0 NOT NULL, date_debut DATE NOT NULL --(DC2Type:date_immutable)
        )');
        $this->addSql('INSERT INTO tarification (id, bleu_hc, bleu_hp, blanc_hc, blanc_hp, rouge_hc, rouge_hp, data_gouv_id, tarif_force, date_debut) SELECT id, bleu_hc, bleu_hp, blanc_hc, blanc_hp, rouge_hc, rouge_hp, data_gouv_id, tarif_force, date_debut FROM __temp__tarification');
        $this->addSql('DROP TABLE __temp__tarification');
    }
}
