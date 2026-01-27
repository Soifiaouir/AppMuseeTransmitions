<?php

namespace App\Tests\Entity;

use App\Entity\Card;
use App\Entity\Media;
use App\Entity\MoreInfo;
use App\Entity\Theme;
use PHPUnit\Framework\TestCase;

class CardTest extends TestCase
{
    public function testGetAndSetTitle(): void
    {
        // Arrange
        $card = new Card();
        $expected = 'Mon titre de carte';

        // Act
        $card->setTitle($expected);

        // Assert
        $this->assertSame($expected, $card->getTitle());
    }

    public function testGetAndSetDetail(): void
    {
        // Arrange
        $card = new Card();
        $expected = 'Description détaillée de la carte';

        // Act
        $card->setDetail($expected);

        // Assert
        $this->assertSame($expected, $card->getDetail());
    }

    public function testGetId(): void
    {
        // Arrange
        $card = new Card();

        // Assert - L'ID est null avant persistance
        $this->assertNull($card->getId());
    }

    public function testSetThemeToNull(): void
    {
        // Arrange
        $card = new Card();
        $theme = new Theme();
        $card->setTheme($theme);

        // Act
        $card->setTheme(null);

        // Assert
        $this->assertNull($card->getTheme());
    }

    public function testThemeAdd(): void
    {
        // Arrange
        $theme = new Theme();
        $card = new Card();
        $card->setTheme($theme);

        // Act
        $theme->addCard($card);

        // Assert
        $this->assertTrue($theme->getCards()->contains($card));
    }

    public function testThemeRemove(): void
    {
        // Arrange
        $theme = new Theme();
        $card = new Card();
        $theme->addCard($card);

        // Act
        $theme->removeCard($card);

        // Assert
        $this->assertFalse($theme->getCards()->contains($card));
    }

    public function testAddMedia(): void
    {
        // Arrange
        $card = new Card();
        $media = new Media();

        // Act
        $card->addMedia($media);

        // Assert
        $this->assertCount(1, $card->getMedias());
        $this->assertTrue($card->getMedias()->contains($media));
    }

    public function testRemoveMedia(): void
    {
        // Arrange
        $card = new Card();
        $media = new Media();
        $card->addMedia($media);

        // Act
        $card->removeMedia($media);

        // Assert
        $this->assertCount(0, $card->getMedias());
        $this->assertFalse($card->getMedias()->contains($media));
    }

    public function testGetBackgroundImageUrls(): void
    {
        // Arrange
        $card = new Card();
        $mediaImage = new Media();
        $mediaImage->setType('image');
        $mediaImage->setName('123');
        $mediaImage->setExtensionFile('jpg');
        $card->addMedia($mediaImage);

        $mediaVideo = new Media();
        $mediaVideo->setType('video');
        $mediaVideo->setName('456');
        $mediaVideo->setExtensionFile('mp4');
        $card->addMedia($mediaVideo);

        // Act
        $urls = $card->getBackgroundImageUrls();

        // Assert
        $this->assertCount(1, $urls);
        $this->assertSame('/uploads/media/image/123.jpg', $urls[0]);
    }

    public function testAddMoreInfo(): void
    {
        // Arrange
        $card = new Card();
        $moreInfo = new MoreInfo();

        // Act
        $card->addMoreInfo($moreInfo);

        // Assert
        $this->assertCount(1, $card->getMoreInfos());
        $this->assertTrue($card->getMoreInfos()->contains($moreInfo));
        $this->assertSame($card, $moreInfo->getCard());
    }

    public function testRemoveMoreInfo(): void
    {
        // Arrange
        $card = new Card();
        $moreInfo = new MoreInfo();
        $card->addMoreInfo($moreInfo);

        // Act
        $card->removeMoreInfo($moreInfo);

        // Assert
        $this->assertCount(0, $card->getMoreInfos());
        $this->assertFalse($card->getMoreInfos()->contains($moreInfo));
        $this->assertNull($moreInfo->getCard());
    }

    public function testGetAndSetTextColor(): void
    {
        // Arrange
        $card = new Card();
        $color = new \App\Entity\Color();

        // Act
        $card->setTextColor($color);

        // Assert
        $this->assertSame($color, $card->getTextColor());
    }

    public function testGetAndSetBackgroundColor(): void
    {
        // Arrange
        $card = new Card();
        $color = new \App\Entity\Color();

        // Act
        $card->setBackgroundColor($color);

        // Assert
        $this->assertSame($color, $card->getBackgroundColor());
    }
}
