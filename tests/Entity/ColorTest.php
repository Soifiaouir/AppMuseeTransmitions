<?php

namespace App\Tests\Entity;

use App\Entity\Color;
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
    public function testConstructorColor(): void
    {
        $color = new Color();
        // Ici, on ne teste plus $themes car la relation ManyToMany Theme n'existe plus
        // On garde juste un test de base pour l'entité
        $this->assertNull($color->getName());
        $this->assertNull($color->getColorCode());
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

    // Les tests ci‑dessous sont retirés car Color n'a plus de relation ManyToMany avec Theme
    // public function testAddThemeAddsThemeToCollection(): void { ... }
    // public function testAddThemeEdit(): void { ... }
    // public function testAddThemeDoesNotAddDuplicates(): void { ... }
    // public function testRemoveThemeRemovesThemeFromCollection(): void { ... }
    // public function testRemoveThemeUpdatesInverseRelation(): void { ... }
}
