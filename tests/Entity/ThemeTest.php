<?php

namespace App\Tests\Entity;

use App\Entity\Card;
use App\Entity\Color;
use App\Entity\Theme;
use PHPUnit\Framework\TestCase;

/**
 * Le constructeur initialise archived = false, dateOfCreation, cards, colors
 * Les getters/setters : name, archived
 * __toString() retourne le nom
 * addColor() / removeColor() (même logique que addTheme)
 * addCard() / removeCard()
 * setDateOfCreationToday() met bien la date à aujourd'hui
 */
class ThemeTest extends TestCase
{
    public function testConstructorTheme(): void
    {
        $theme = new Theme();

        $this->assertCount(0, $theme->getColors());
        $this->assertCount(0, $theme->getCards());

        //Test séparé pour chaque collection
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $theme->getColors());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $theme->getCards());

        $this->assertFalse($theme->isArchived());
        $this->assertInstanceOf(\DateTimeImmutable::class, $theme->getDateOfCreation());
    }

    public function testGetAndSetName(): void{
        $theme = new Theme();
        $expected ='Banane';

       //act
        $theme->setName($expected);

        //assert
        $this->assertSame($expected, $theme->getName());
    }
    public function testGetAndSetArchived(): void{
        $theme = new Theme();
        $expected = false;

        //act
        $theme->setArchived($expected);

        //assert
        $this->assertSame($expected, $theme->isArchived());
    }

    public function testToString(): void{
        $theme = new Theme();
        $theme->setName('Banane');

        //Act
        $result = (string)$theme;

        //Assert
        $this->AssertEquals('Banane', $result);
    }

    public function testAddCard(): void
    {
        $theme = new Theme();
        $card = new Card();

        //Act
        $theme->addCard($card);

        //Assert
        $this->assertCount(1, $theme->getCards());
        $this->assertTrue($theme->getCards()->contains($card));
    }

    public function testAddColor(): void
    {
        $theme = new Theme();
        $color = new Color();

        //Act
        $theme->addColor($color);

        //Assert
        $this->assertCount(1, $theme->getColors());
        $this->assertTrue($theme->getColors()->contains($color));
    }

    public function testAddCardEdit(): void{
        $theme = new Theme();
        $card = new Card();

        //act
        $theme->addCard($card);

        //Assert
        $this->assertTrue($theme->getCards()->contains($card));
    }

    public function testAddColorEdit(): void{
        $theme = new Theme();
        $color = new Color();

        //act
        $theme->addColor($color);

        //Assert
        $this->assertTrue($theme->getColors()->contains($color));
    }

    public function testRemoveCard(): void{
        $theme = new Theme();
        $card = new Card();
        $theme->addCard($card);

        //act
        $theme->removeCard($card);

        //Assert
        $this->assertFalse($theme->getCards()->contains($card));
    }

    public function testRemoveColor(): void{
        $theme = new Theme();
        $color = new Color();
        $theme->addColor($color);

        //act
        $theme->removeColor($color);

        //Assert
        $this->assertFalse($theme->getColors()->contains($color));
    }

    public function testSetDateOfCreation(): void
    {
        $theme = new Theme();

        // Act
        $theme->setDateOfCreation();

        // Assert
        $this->assertInstanceOf(\DateTimeImmutable::class, $theme->getDateOfCreation());

    }


}
