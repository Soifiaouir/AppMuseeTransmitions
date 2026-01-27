<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ColorControllerTest extends WebTestCase
{
    /**
     * Test 1 : Vérifier que la page de liste des couleurs s'affiche
     *
     * Arrange (Préparation) : On crée un client (= navigateur simulé)
     * Act (Action) : On demande la page GET /color/
     * Assert (Vérification) : On vérifie que la page répond avec succès (code 200)
     */
    public function testColorListPageIsSuccessful(): void
    {
        // Arrange : Créer un client (navigateur simulé)
        $client = self::createClient();

        // Act : Visiter la page de liste des couleurs
        $client->request('GET', '/color/');

        // Assert : Vérifier que la page s'affiche correctement
        $this->assertResponseIsSuccessful(); // Code HTTP 200
        $this->assertSelectorExists('h1'); // Il y a bien un titre h1 sur la page
    }

    /**
     * Test 2 : Vérifier que la page d'ajout de couleur s'affiche
     */
    public function testColorAddPageIsSuccessful(): void
    {
        // Arrange
        $client = self::createClient();

        // Act : Visiter la page d'ajout
        $client->request('GET', '/color/add');

        // Assert : La page s'affiche et contient un formulaire
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form'); // Il y a bien un formulaire
    }

    /**
     * Test 3 : Vérifier qu'on peut créer une couleur via le formulaire
     *
     * Ce test simule le remplissage et l'envoi d'un formulaire
     */
    public function testCreateColorWithForm(): void
    {
        // Arrange
        $client = self::createClient();

        // Act : Aller sur la page d'ajout
        $crawler = $client->request('GET', '/color/add');

        // Remplir et soumettre le formulaire
        $form = $crawler->selectButton('Enregistrer')->form([
            'color[name]' => 'Rouge Test',
            'color[colorCode]' => '#FF0000',
        ]);

        $client->submit($form);

        // Assert : On est redirigé vers la liste
        $this->assertResponseRedirects('/color/');

        // Suivre la redirection
        $client->followRedirect();

        // Vérifier qu'on est bien sur la liste
        $this->assertResponseIsSuccessful();
    }

    /**
     * Test 4 : Vérifier que la validation fonctionne
     *
     * Si on envoie un formulaire vide, il doit y avoir des erreurs
     */
    public function testCreateColorWithInvalidDataShowsErrors(): void
    {
        // Arrange
        $client = self::createClient();

        // Act : Aller sur la page d'ajout
        $crawler = $client->request('GET', '/color/add');

        // Soumettre le formulaire VIDE (sans remplir les champs)
        $form = $crawler->selectButton('Enregistrer')->form([
            'color[name]' => '',      // Vide
            'color[colorCode]' => '', // Vide
        ]);

        $client->submit($form);

        // Assert : On reste sur la page (pas de redirection)
        $this->assertResponseIsSuccessful();

        // Et il y a des messages d'erreur de validation
        $this->assertSelectorExists('.invalid-feedback, .form-error');
    }

    /**
     * Test 5 : Vérifier qu'un code couleur invalide est rejeté
     */
    public function testCreateColorWithInvalidColorCode(): void
    {
        // Arrange
        $client = self::createClient();

        // Act
        $crawler = $client->request('GET', '/color/add');

        // Soumettre avec un code couleur invalide
        $form = $crawler->selectButton('Enregistrer')->form([
            'color[name]' => 'Test',
            'color[colorCode]' => 'INVALID',
        ]);

        $client->submit($form);

        // Assert : Erreur de validation
        $this->assertResponseIsSuccessful(); // Reste sur la page
        $this->assertSelectorExists('.invalid-feedback, .form-error');
    }
}
