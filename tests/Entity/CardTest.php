<?php

namespace App\Tests\Entity;

use App\Entity\Card;
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

    public function testGetAndSetMoreInfoTitle(): void
    {
        // Arrange
        $card = new Card();
        $expected = 'Plus d\'informations';

        // Act
        $card->setMoreInfoTitle($expected);

        // Assert
        $this->assertSame($expected, $card->getMoreInfoTitle());
    }

    public function testGetAndSetMoreInfoDetails(): void
    {
        // Arrange
        $card = new Card();
        $expected = 'Détails supplémentaires';

        // Act
        $card->setMoreInfoDetails($expected);

        // Assert
        $this->assertSame($expected, $card->getMoreInfoDetails());
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
        $theme = new Theme();
        $card = new Card();
        $card->setTheme($theme);

        $theme->addCard($card);

        $this->assertContains($card, $theme->getCards()->toArray());
    }

    public function testThemeRemove(): void{
        $theme = new Theme();
        $card = new Card();
        $theme->addCard($card);

        $theme->removeCard($card);
        $this->assertFalse($theme->getCards()->contains($card));
    }


}