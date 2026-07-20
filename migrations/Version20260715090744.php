<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260715090744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, restaurant_id INT NOT NULL, client_id INT NOT NULL, uuid VARCHAR(36) NOT NULL, guest_number SMALLINT NOT NULL, booking_at DATETIME NOT NULL, allergy VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_E00CEDDED17F50A6 (uuid), INDEX IDX_E00CEDDEB1E7706E (restaurant_id), INDEX IDX_E00CEDDE19EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEB1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE19EB6921 FOREIGN KEY (client_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE food ADD menu_id INT NOT NULL, CHANGE price price INT NOT NULL');
        $this->addSql('ALTER TABLE food ADD CONSTRAINT FK_D43829F7CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D43829F7D17F50A6 ON food (uuid)');
        $this->addSql('CREATE INDEX IDX_D43829F7CCD7E912 ON food (menu_id)');
        $this->addSql('ALTER TABLE picture ADD uuid VARCHAR(36) NOT NULL, ADD path VARCHAR(255) NOT NULL, DROP slug');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_16DB4F89D17F50A6 ON picture (uuid)');
        $this->addSql('ALTER TABLE restaurant ADD owner_id INT NOT NULL, ADD uuid VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE restaurant ADD CONSTRAINT FK_EB95123F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EB95123FD17F50A6 ON restaurant (uuid)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EB95123F7E3C61F9 ON restaurant (owner_id)');
        $this->addSql('ALTER TABLE user ADD uuid VARCHAR(36) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649D17F50A6 ON user (uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEB1E7706E');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE19EB6921');
        $this->addSql('DROP TABLE booking');
        $this->addSql('ALTER TABLE food DROP FOREIGN KEY FK_D43829F7CCD7E912');
        $this->addSql('DROP INDEX UNIQ_D43829F7D17F50A6 ON food');
        $this->addSql('DROP INDEX IDX_D43829F7CCD7E912 ON food');
        $this->addSql('ALTER TABLE food DROP menu_id, CHANGE price price SMALLINT NOT NULL');
        $this->addSql('DROP INDEX UNIQ_16DB4F89D17F50A6 ON picture');
        $this->addSql('ALTER TABLE picture ADD slug VARCHAR(128) NOT NULL, DROP uuid, DROP path');
        $this->addSql('ALTER TABLE restaurant DROP FOREIGN KEY FK_EB95123F7E3C61F9');
        $this->addSql('DROP INDEX UNIQ_EB95123FD17F50A6 ON restaurant');
        $this->addSql('DROP INDEX UNIQ_EB95123F7E3C61F9 ON restaurant');
        $this->addSql('ALTER TABLE restaurant DROP owner_id, DROP uuid');
        $this->addSql('DROP INDEX UNIQ_8D93D649D17F50A6 ON user');
        $this->addSql('ALTER TABLE user DROP uuid');
    }
}
