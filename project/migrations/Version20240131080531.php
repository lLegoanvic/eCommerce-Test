<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240131080531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_pictures DROP FOREIGN KEY FK_44CC4557E00EE68D');
        $this->addSql('DROP INDEX IDX_44CC4557E00EE68D ON product_pictures');
        $this->addSql('ALTER TABLE product_pictures CHANGE id_product_id product_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_pictures ADD CONSTRAINT FK_44CC45574584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('CREATE INDEX IDX_44CC45574584665A ON product_pictures (product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_pictures DROP FOREIGN KEY FK_44CC45574584665A');
        $this->addSql('DROP INDEX IDX_44CC45574584665A ON product_pictures');
        $this->addSql('ALTER TABLE product_pictures CHANGE product_id id_product_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_pictures ADD CONSTRAINT FK_44CC4557E00EE68D FOREIGN KEY (id_product_id) REFERENCES product (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_44CC4557E00EE68D ON product_pictures (id_product_id)');
    }
}
