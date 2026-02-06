<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206162140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE card (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, detail LONGTEXT NOT NULL, archived TINYINT DEFAULT NULL, theme_id INT NOT NULL, text_color_id INT DEFAULT NULL, background_color_id INT DEFAULT NULL, INDEX IDX_161498D359027487 (theme_id), INDEX IDX_161498D3CC9893A7 (text_color_id), INDEX IDX_161498D3A1A51272 (background_color_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE card_media (card_id INT NOT NULL, media_id INT NOT NULL, INDEX IDX_521850CA4ACC9A20 (card_id), INDEX IDX_521850CAEA9FDD75 (media_id), PRIMARY KEY (card_id, media_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE color (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, color_code VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE media (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, userGivenName VARCHAR(255) NOT NULL, size INT DEFAULT NULL, extension_file VARCHAR(255) NOT NULL, uploaded_at DATETIME NOT NULL, archived TINYINT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE media_theme (media_id INT NOT NULL, theme_id INT NOT NULL, INDEX IDX_886C23B9EA9FDD75 (media_id), INDEX IDX_886C23B959027487 (theme_id), PRIMARY KEY (media_id, theme_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE more_info (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, details LONGTEXT NOT NULL, card_id INT DEFAULT NULL, INDEX IDX_31AE29F04ACC9A20 (card_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE refresh_tokens (refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, UNIQUE INDEX UNIQ_9BACE7E1C74F2195 (refresh_token), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE theme (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, date_of_creation DATETIME NOT NULL, archived TINYINT NOT NULL, background_image_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, theme_background_color_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_9775E708E6DA28AA (background_image_id), INDEX IDX_9775E708B03A8386 (created_by_id), INDEX IDX_9775E7085B7CFF66 (theme_background_color_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, password_change TINYINT NOT NULL, password_change_date DATETIME DEFAULT NULL, temp_password VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE card ADD CONSTRAINT FK_161498D359027487 FOREIGN KEY (theme_id) REFERENCES theme (id)');
        $this->addSql('ALTER TABLE card ADD CONSTRAINT FK_161498D3CC9893A7 FOREIGN KEY (text_color_id) REFERENCES color (id)');
        $this->addSql('ALTER TABLE card ADD CONSTRAINT FK_161498D3A1A51272 FOREIGN KEY (background_color_id) REFERENCES color (id)');
        $this->addSql('ALTER TABLE card_media ADD CONSTRAINT FK_521850CA4ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE card_media ADD CONSTRAINT FK_521850CAEA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE media_theme ADD CONSTRAINT FK_886C23B9EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE media_theme ADD CONSTRAINT FK_886C23B959027487 FOREIGN KEY (theme_id) REFERENCES theme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE more_info ADD CONSTRAINT FK_31AE29F04ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id)');
        $this->addSql('ALTER TABLE theme ADD CONSTRAINT FK_9775E708E6DA28AA FOREIGN KEY (background_image_id) REFERENCES media (id)');
        $this->addSql('ALTER TABLE theme ADD CONSTRAINT FK_9775E708B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE theme ADD CONSTRAINT FK_9775E7085B7CFF66 FOREIGN KEY (theme_background_color_id) REFERENCES color (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE card DROP FOREIGN KEY FK_161498D359027487');
        $this->addSql('ALTER TABLE card DROP FOREIGN KEY FK_161498D3CC9893A7');
        $this->addSql('ALTER TABLE card DROP FOREIGN KEY FK_161498D3A1A51272');
        $this->addSql('ALTER TABLE card_media DROP FOREIGN KEY FK_521850CA4ACC9A20');
        $this->addSql('ALTER TABLE card_media DROP FOREIGN KEY FK_521850CAEA9FDD75');
        $this->addSql('ALTER TABLE media_theme DROP FOREIGN KEY FK_886C23B9EA9FDD75');
        $this->addSql('ALTER TABLE media_theme DROP FOREIGN KEY FK_886C23B959027487');
        $this->addSql('ALTER TABLE more_info DROP FOREIGN KEY FK_31AE29F04ACC9A20');
        $this->addSql('ALTER TABLE theme DROP FOREIGN KEY FK_9775E708E6DA28AA');
        $this->addSql('ALTER TABLE theme DROP FOREIGN KEY FK_9775E708B03A8386');
        $this->addSql('ALTER TABLE theme DROP FOREIGN KEY FK_9775E7085B7CFF66');
        $this->addSql('DROP TABLE card');
        $this->addSql('DROP TABLE card_media');
        $this->addSql('DROP TABLE color');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE media_theme');
        $this->addSql('DROP TABLE more_info');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE theme');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
