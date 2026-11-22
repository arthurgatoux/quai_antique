<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute les entités Category, Food, Menu, Booking et leurs tables de jointure.
 */
final class Version20261121190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des tables category, food, menu, booking, food_category et menu_category';
    }

    public function up(Schema $schema): void
    {
        $opts = 'DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB';
        $dt = "COMMENT '(DC2Type:datetime_immutable)'";

        $this->addSql("CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL $dt, updated_at DATETIME DEFAULT NULL $dt, PRIMARY KEY(id)) $opts");
        $this->addSql("CREATE TABLE food (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(64) NOT NULL, description LONGTEXT NOT NULL, price SMALLINT NOT NULL, created_at DATETIME NOT NULL $dt, updated_at DATETIME DEFAULT NULL $dt, PRIMARY KEY(id)) $opts");
        $this->addSql("CREATE TABLE food_category (food_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_2E013E83BA8E87C4 (food_id), INDEX IDX_2E013E8312469DE2 (category_id), PRIMARY KEY(food_id, category_id)) $opts");
        $this->addSql("CREATE TABLE menu (id INT AUTO_INCREMENT NOT NULL, restaurant_id INT NOT NULL, title VARCHAR(64) NOT NULL, description LONGTEXT NOT NULL, price SMALLINT NOT NULL, created_at DATETIME NOT NULL $dt, updated_at DATETIME DEFAULT NULL $dt, INDEX IDX_7D053A93B1E7706E (restaurant_id), PRIMARY KEY(id)) $opts");
        $this->addSql("CREATE TABLE menu_category (menu_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_2A1D5C57CCD7E912 (menu_id), INDEX IDX_2A1D5C5712469DE2 (category_id), PRIMARY KEY(menu_id, category_id)) $opts");
        $this->addSql("CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, restaurant_id INT NOT NULL, client_id INT NOT NULL, guest_number SMALLINT NOT NULL, order_date DATE NOT NULL COMMENT '(DC2Type:date_immutable)', order_hour DATETIME NOT NULL $dt, allergy VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL $dt, updated_at DATETIME DEFAULT NULL $dt, INDEX IDX_E00CEDDEB1E7706E (restaurant_id), INDEX IDX_E00CEDDE19EB6921 (client_id), PRIMARY KEY(id)) $opts");

        $this->addSql('ALTER TABLE food_category ADD CONSTRAINT FK_2E013E83BA8E87C4 FOREIGN KEY (food_id) REFERENCES food (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE food_category ADD CONSTRAINT FK_2E013E8312469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE menu ADD CONSTRAINT FK_7D053A93B1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id)');
        $this->addSql('ALTER TABLE menu_category ADD CONSTRAINT FK_2A1D5C57CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE menu_category ADD CONSTRAINT FK_2A1D5C5712469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEB1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE19EB6921 FOREIGN KEY (client_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEB1E7706E');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE19EB6921');
        $this->addSql('ALTER TABLE menu_category DROP FOREIGN KEY FK_2A1D5C57CCD7E912');
        $this->addSql('ALTER TABLE menu_category DROP FOREIGN KEY FK_2A1D5C5712469DE2');
        $this->addSql('ALTER TABLE menu DROP FOREIGN KEY FK_7D053A93B1E7706E');
        $this->addSql('ALTER TABLE food_category DROP FOREIGN KEY FK_2E013E83BA8E87C4');
        $this->addSql('ALTER TABLE food_category DROP FOREIGN KEY FK_2E013E8312469DE2');
        $this->addSql('DROP TABLE booking');
        $this->addSql('DROP TABLE menu_category');
        $this->addSql('DROP TABLE menu');
        $this->addSql('DROP TABLE food_category');
        $this->addSql('DROP TABLE food');
        $this->addSql('DROP TABLE category');
    }
}
