<?php

namespace App\DataFixtures;

use App\Entity\Card;
use App\Entity\Color;
use App\Entity\Theme;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{

    public function load(ObjectManager $manager): void
    {
        $this->addTheme($manager);
        $this->addColor($manager);
        $this->addCards($manager);

    }

    public function addTheme(ObjectManager $manager): void
    {
        $faker  = Factory::create('fr_FR');
        $colors = $manager->getRepository(Color::class)->findAll();
        $cards = $manager->getRepository(Card::class)->findAll();

        for ($i = 0; $i < 5; $i++) {
            $theme = new Theme();
            $theme->setName($faker->name())
                ->setArchived(false);
            $manager->persist($theme);
        }
        $manager->flush();
    }

    public function addColor(ObjectManager $manager): void{
        $faker  = Factory::create('fr_FR');
        for ($i = 0; $i < 15; $i++) {
            $color = new Color();
            $color->setName($faker->colorName())
                ->setColorCode($faker->hexColor());
            $manager->persist($color);
        }
        $manager->flush();

    }

    public function addCards(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $themes = $manager->getRepository(Theme::class)->findAll();

        for ($i = 0; $i < 15; $i++) {
            $card = new Card();
            $card->setTitle($faker->title())
                ->setDetail($faker->paragraph())
                ->setMoreInfoTitle($faker->title())
                ->setMoreInfoDetails($faker->paragraph())
                ->setTheme($themes[array_rand($themes)]);
            $manager->persist($card);
        }
        $manager->flush();
    }

}
