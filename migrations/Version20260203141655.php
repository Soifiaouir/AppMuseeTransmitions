<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203141655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE card ADD CONSTRAINT FK_161498D359027487 FOREIGN KEY (theme_id) REFERENCES theme (id)');
        $this->addSql('ALTER TABLE card ADD CONSTRAINT FK_161498D3CC9893A7 FOREIGN KEY (text_color_id) REFERENCES color (id)');
        $this->addSql('ALTER TABLE card ADD CONSTRAINT FK_161498D3A1A51272 FOREIGN KEY (background_color_id) REFERENCES color (id)');
        $this->addSql('ALTER TABLE media_card ADD CONSTRAINT FK_D5534700EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE media_card ADD CONSTRAINT FK_D55347004ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id) ON DELETE CASCADE');
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
        $this->addSql('ALTER TABLE media_card DROP FOREIGN KEY FK_D5534700EA9FDD75');
        $this->addSql('ALTER TABLE media_card DROP FOREIGN KEY FK_D55347004ACC9A20');
        $this->addSql('ALTER TABLE media_theme DROP FOREIGN KEY FK_886C23B9EA9FDD75');
        $this->addSql('ALTER TABLE media_theme DROP FOREIGN KEY FK_886C23B959027487');
        $this->addSql('ALTER TABLE more_info DROP FOREIGN KEY FK_31AE29F04ACC9A20');
        $this->addSql('ALTER TABLE theme DROP FOREIGN KEY FK_9775E708E6DA28AA');
        $this->addSql('ALTER TABLE theme DROP FOREIGN KEY FK_9775E708B03A8386');
        $this->addSql('ALTER TABLE theme DROP FOREIGN KEY FK_9775E7085B7CFF66');
    }
}
