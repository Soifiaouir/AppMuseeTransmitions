<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ColorControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        public function testColorListPageIsSuccessful(): void
    {
        $client = self::createClient();      // Arrange
        $client->request('GET', '/colors');    // Act

        $this->assertResponseIsSuccessful();   // Assert
        $this->assertSelectorExists('table');  // Assert sur le HTML
    }
}
