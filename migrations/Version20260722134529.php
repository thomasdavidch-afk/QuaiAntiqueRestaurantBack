<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260722134529 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE food DROP FOREIGN KEY FK_D43829F7CCD7E912');
        $this->addSql('DROP INDEX IDX_D43829F7CCD7E912 ON food');
        $this->addSql('ALTER TABLE food CHANGE menu_id category_id INT NOT NULL');
        $this->addSql('ALTER TABLE food ADD CONSTRAINT FK_D43829F712469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('CREATE INDEX IDX_D43829F712469DE2 ON food (category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE food DROP FOREIGN KEY FK_D43829F712469DE2');
        $this->addSql('DROP INDEX IDX_D43829F712469DE2 ON food');
        $this->addSql('ALTER TABLE food CHANGE category_id menu_id INT NOT NULL');
        $this->addSql('ALTER TABLE food ADD CONSTRAINT FK_D43829F7CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id)');
        $this->addSql('CREATE INDEX IDX_D43829F7CCD7E912 ON food (menu_id)');
    }
}
