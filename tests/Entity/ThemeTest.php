<?php

namespace App\Tests\Entity;

use App\Entity\Card;
use App\Entity\Theme;
use PHPUnit\Framework\TestCase;

/**
 * Le constructeur initialise archived = false, dateOfCreation, cards, medias
 * Les getters/setters : name, archived
 * __toString() retourne le nom
 * addCard() / removeCard()
 * setDateOfCreationToday() met bien la date à aujourd'hui
 */
class ThemeTest extends TestCase
{
    public function testConstructorTheme(): void
    {
        $theme = new Theme();

        // ARRANGE (implicite ici)

        // ASSERT
        $this->assertCount(0, $theme->getCards());
        $this->assertCount(0, $theme->getMedias());

        // Test séparé pour chaque collection
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $theme->getCards());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $theme->getMedias());

        $this->assertFalse($theme->isArchived());
        $this->assertInstanceOf(\DateTimeImmutable::class, $theme->getDateOfCreation());
    }

    public function testGetAndSetName(): void
    {
        // ARRANGE
        $theme = new Theme();
        $expected = 'Banane';

        // ACT
        $theme->setName($expected);

        // ASSERT
        $this->assertSame($expected, $theme->getName());
    }

    public function testGetAndSetArchived(): void
    {
        // ARRANGE
        $theme = new Theme();
        $expected = false;

        // ACT
        $theme->setArchived($expected);

        // ASSERT
        $this->assertSame($expected, $theme->isArchived());
    }

    public function testToString(): void
    {
        // ARRANGE
        $theme = new Theme();
        $theme->setName('Banane');

        // ACT
        $result = (string) $theme;

        // ASSERT
        $this->assertEquals('Banane', $result);
    }

    public function testAddCard(): void
    {
        // ARRANGE
        $theme = new Theme();
        $card = new Card();

        // ACT
        $theme->addCard($card);

        // ASSERT
        $this->assertCount(1, $theme->getCards());
        $this->assertTrue($theme->getCards()->contains($card));
    }

    public function testAddCardEdit(): void
    {
        // ARRANGE
        $theme = new Theme();
        $card = new Card();

        // ACT
        $theme->addCard($card);

        // ASSERT
        $this->assertTrue($theme->getCards()->contains($card));
    }

    public function testRemoveCard(): void
    {
        // ARRANGE
        $theme = new Theme();
        $card = new Card();
        $theme->addCard($card);

        // ACT
        $theme->removeCard($card);

        // ASSERT
        $this->assertCount(0, $theme->getCards());
        $this->assertFalse($theme->getCards()->contains($card));
    }

    public function testAddMedia(): void
    {
        // ARRANGE
        $theme = new Theme();
        $media = new \App\Entity\Media();

        // ACT
        $theme->addMedia($media);

        // ASSERT
        $this->assertCount(1, $theme->getMedias());
        $this->assertTrue($theme->getMedias()->contains($media));
    }

    public function testRemoveMedia(): void
    {
        // ARRANGE
        $theme = new Theme();
        $media = new \App\Entity\Media();
        $theme->addMedia($media);

        // ACT
        $theme->removeMedia($media);

        // ASSERT
        $this->assertCount(0, $theme->getMedias());
        $this->assertFalse($theme->getMedias()->contains($media));
    }

    public function testSetDateOfCreation(): void
    {
        // ARRANGE
        $theme = new Theme();

        // ACT
        $theme->setDateOfCreation();

        // ASSERT
        $this->assertInstanceOf(\DateTimeImmutable::class, $theme->getDateOfCreation());
    }
}
