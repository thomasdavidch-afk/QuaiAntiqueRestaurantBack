<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260803093429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE food ADD menu_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE food ADD CONSTRAINT FK_D43829F7CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_D43829F7CCD7E912 ON food (menu_id)');
        $this->addSql('ALTER TABLE menu CHANGE description description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE food DROP FOREIGN KEY FK_D43829F7CCD7E912');
        $this->addSql('DROP INDEX IDX_D43829F7CCD7E912 ON food');
        $this->addSql('ALTER TABLE food DROP menu_id');
        $this->addSql('ALTER TABLE menu CHANGE description description LONGTEXT NOT NULL');
    }
}
