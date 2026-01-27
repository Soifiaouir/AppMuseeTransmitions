<?php

namespace App\Tests\Entity;

use App\Entity\Card;
use App\Entity\Media;
use App\Entity\Theme;
use PHPUnit\Framework\TestCase;

/**
 * Pour l'entité Media, on va tester :
 *
 * Le constructeur : Vérifie que $cards et $themes sont bien initialisés comme des Collections vides
 * Les getters/setters simples : getName(), setName(), getType(), setType(), getUserGivenName(), setUserGivenName(),
 * getSize(), setSize(), getExtensionFile(), setExtensionFile(), getUploadedAt(), setUploadedAt()
 * Les méthodes utilitaires : getFilePath(), getFullFileName(), getPublicPath()
 * La gestion des relations ManyToMany :
 *
 * addCard() : Ajoute une carte à la collection
 * removeCard() : Supprime une carte de la collection
 * addTheme() : Ajoute un thème à la collection
 * removeTheme() : Supprime un thème de la collection
 */

class MediaTest extends TestCase
{
    public function testConstructorMedia(): void
    {
        // ARRANGE
        $media = new Media();

        // ASSERT
        $this->assertCount(0, $media->getCards());
        $this->assertCount(0, $media->getThemes());
        $this->assertInstanceOf(\DateTimeImmutable::class, $media->getUploadedAt());
    }

    public function testGetAndSetName(): void
    {
        // ARRANGE
        $media = new Media();
        $expectedName = 'photo-principale';

        // ACT
        $media->setName($expectedName);

        // ASSERT
        $this->assertSame($expectedName, $media->getName());
    }

    public function testGetAndSetType(): void
    {
        // ARRANGE
        $media = new Media();
        $expectedType = 'image';

        // ACT
        $media->setType($expectedType);

        // ASSERT
        $this->assertSame($expectedType, $media->getType());
    }

    public function testGetAndSetUserGivenName(): void
    {
        // ARRANGE
        $media = new Media();
        $expectedUserGivenName = 'Photo du musée';

        // ACT
        $media->setUserGivenName($expectedUserGivenName);

        // ASSERT
        $this->assertSame($expectedUserGivenName, $media->getUserGivenName());
    }

    public function testGetAndSetSize(): void
    {
        // ARRANGE
        $media = new Media();
        $expectedSize = 1024 * 1024; // 1 Mo

        // ACT
        $media->setSize($expectedSize);

        // ASSERT
        $this->assertSame($expectedSize, $media->getSize());
    }

    public function testGetAndSetExtensionFile(): void
    {
        // ARRANGE
        $media = new Media();
        $expectedExtension = 'jpg';

        // ACT
        $media->setExtensionFile($expectedExtension);

        // ASSERT
        $this->assertSame($expectedExtension, $media->getExtensionFile());
    }

    public function testGetAndSetUploadedAt(): void
    {
        // ARRANGE
        $media = new Media();
        $expectedDate = new \DateTimeImmutable('2025-01-01 10:00:00');

        // ACT
        $media->setUploadedAt($expectedDate);

        // ASSERT
        $this->assertSame($expectedDate, $media->getUploadedAt());
    }

    public function testGetFilePath(): void
    {
        // ARRANGE
        $media = new Media();
        $media->setType('image');
        $media->setName('123');
        $media->setExtensionFile('jpg');

        // ACT
        $result = $media->getFilePath();

        // ASSERT
        $this->assertSame('image/123.jpg', $result);
    }

    public function testGetFullFileName(): void
    {
        // ARRANGE
        $media = new Media();
        $media->setName('123');
        $media->setExtensionFile('png');

        // ACT
        $result = $media->getFullFileName();

        // ASSERT
        $this->assertSame('123.png', $result);
    }

    public function testGetPublicPathReturnsCorrectPath(): void
    {
        // ARRANGE
        $media = new Media();
        $media->setType('image');
        $media->setName('123');
        $media->setExtensionFile('jpg');

        // ACT
        $result = $media->getPublicPath();

        // ASSERT
        $this->assertSame('/uploads/media/image/123.jpg', $result);
    }

    public function testAddCardAddsCardToCollection(): void
    {
        // ARRANGE
        $media = new Media();
        $card = new Card();

        // ACT
        $media->addCard($card);

        // ASSERT
        $this->assertCount(1, $media->getCards());
        $this->assertTrue($media->getCards()->contains($card));
    }

    public function testAddCardDoesNotAddDuplicates(): void
    {
        // ARRANGE
        $media = new Media();
        $card = new Card();

        // ACT
        $media->addCard($card);
        $media->addCard($card);

        // ASSERT
        $this->assertCount(1, $media->getCards());
    }

    public function testRemoveCardRemovesCardFromCollection(): void
    {
        // ARRANGE
        $media = new Media();
        $card = new Card();
        $media->addCard($card);

        // ACT
        $media->removeCard($card);

        // ASSERT
        $this->assertCount(0, $media->getCards());
        $this->assertFalse($media->getCards()->contains($card));
    }

    public function testAddThemeAddsThemeToCollection(): void
    {
        // ARRANGE
        $media = new Media();
        $theme = new Theme();

        // ACT
        $media->addTheme($theme);

        // ASSERT
        $this->assertCount(1, $media->getThemes());
        $this->assertTrue($media->getThemes()->contains($theme));
    }

    public function testAddThemeDoesNotAddDuplicates(): void
    {
        // ARRANGE
        $media = new Media();
        $theme = new Theme();

        // ACT
        $media->addTheme($theme);
        $media->addTheme($theme);

        // ASSERT
        $this->assertCount(1, $media->getThemes());
    }

    public function testRemoveThemeRemovesThemeFromCollection(): void
    {
        // ARRANGE
        $media = new Media();
        $theme = new Theme();
        $media->addTheme($theme);

        // ACT
        $media->removeTheme($theme);

        // ASSERT
        $this->assertCount(0, $media->getThemes());
        $this->assertFalse($media->getThemes()->contains($theme));
    }
}
