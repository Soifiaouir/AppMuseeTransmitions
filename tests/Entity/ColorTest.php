<?php

namespace App\Tests\Entity;

use App\Entity\Color;
use App\Entity\Theme;
use PHPUnit\Framework\TestCase;

/**
 * Pour l'entité Color, on va tester :
 *
 * Le constructeur : Vérifie que $themes est bien initialisé comme une Collection vide
 * Les getters/setters simples : getName(), setName(), getColorCode(), setColorCode()
 * La méthode __toString() : Retourne bien le nom
 * La gestion des relations ManyToMany :
 *
 * addTheme() : Ajoute un thème ET met à jour la relation inverse
 * removeTheme() : Supprime un thème ET met à jour la relation inverse
 */

class ColorTest extends TestCase
{
    public function testConstructorColor(): void{
        $color = new Color();
        $this->assertCount(0, $color->getThemes());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $color->getThemes());
    }

    public function testGetAndSetName(): void
    {
        // ARRANGE(Préparer) : Tu crées les objets nécessaires
        $color = new Color();
        $expectedName = 'Rouge écarlate';

        // ACT(Agir) : Tu exécutes l'action à tester
        $color->setName($expectedName);

        // ASSERT(Vérifier) : Tu vérifies le résultat
        $this->assertSame($expectedName, $color->getName());
    }

    public function testGetAndSetColorCode(): void
    {
        // ARRANGE
        $color = new Color();
        $expectedCode = '#FF5733';

        // ACT
        $color->setColorCode($expectedCode);

        // ASSERT
        $this->assertSame($expectedCode, $color->getColorCode());
    }

    public function testToStringReturnsName(): void
    {
        // ARRANGE
        $color = new Color();
        $color->setName('Bleu azur');

        // ACT
        $result = (string) $color;

        // ASSERT
        $this->assertSame('Bleu azur', $result);
    }

    public function testToStringReturnsEmptyStringWhenNameIsNull(): void
    {
        // ARRANGE
        $color = new Color();

        // ACT
        $result = (string) $color;

        // ASSERT
        $this->assertSame('', $result);
    }

    public function testAddThemeAddsThemeToCollection(): void
    {
        // ARRANGE
        $color = new Color();
        $theme = new Theme();

        // ACT
        $color->addTheme($theme);

        // ASSERT
        $this->assertCount(1, $color->getThemes());
        $this->assertTrue($color->getThemes()->contains($theme));
    }

    public function testAddThemeEdit(): void
    {
        // ARRANGE
        $color = new Color();
        $theme = new Theme();

        // ACT
        $color->addTheme($theme);

        // ASSERT - Vérifie que Theme contient aussi Color
        $this->assertTrue($theme->getColors()->contains($color));
    }

    public function testAddThemeDoesNotAddDuplicates(): void
    {
        // ARRANGE
        $color = new Color();
        $theme = new \App\Entity\Theme();

        // ACT - Ajouter 2 fois le même thème
        $color->addTheme($theme);
        $color->addTheme($theme);

        // ASSERT - Doit rester à 1
        $this->assertCount(1, $color->getThemes());
    }

    public function testRemoveThemeRemovesThemeFromCollection(): void
    {
        // ARRANGE
        $color = new Color();
        $theme = new \App\Entity\Theme();
        $color->addTheme($theme);

        // ACT
        $color->removeTheme($theme);

        // ASSERT
        $this->assertCount(0, $color->getThemes());
        $this->assertFalse($color->getThemes()->contains($theme));
    }

    public function testRemoveThemeUpdatesInverseRelation(): void
    {
        // ARRANGE
        $color = new Color();
        $theme = new \App\Entity\Theme();
        $color->addTheme($theme);

        // ACT
        $color->removeTheme($theme);

        // ASSERT - Vérifie que Theme ne contient plus Color
        $this->assertFalse($theme->getColors()->contains($color));
    }
}
