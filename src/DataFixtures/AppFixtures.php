<?php

namespace App\DataFixtures;

use App\Entity\Card;
use App\Entity\Color;
use App\Entity\Theme;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $userPasswordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->addUsers($manager);
        $this->addTheme($manager);
        $this->addColor($manager);
        $this->addCards($manager);
    }

    public function addUsers(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Utilisateur normal - doit changer son mot de passe
        $user = new User();
        $user->setUsername('user')
            ->setPassword($this->userPasswordHasher->hashPassword($user, 'user'))
            ->setRoles(['ROLE_USER'])
            ->setPasswordChange(true); // Forcé à changer le mot de passe

        $manager->persist($user);

        // Admin - n'a pas besoin de changer son mot de passe
        $userAdmin = new User();
        $userAdmin->setUsername('admin')
            ->setPassword($this->userPasswordHasher->hashPassword($userAdmin, 'admin'))
            ->setRoles(['ROLE_ADMIN'])
            ->setPasswordChange(false) // Admin n'est pas forcé
            ->setPasswordChangeDate(new \DateTime()); // Date de dernier changement

        $manager->persist($userAdmin);

        // Ajout de quelques utilisateurs de test supplémentaires
        for ($i = 1; $i <= 3; $i++) {
            $testUser = new User();
            $testUser->setUsername('user' . $i)
                ->setPassword($this->userPasswordHasher->hashPassword($testUser, 'password123'))
                ->setRoles(['ROLE_USER'])
                ->setPasswordChange(true); // Tous doivent changer leur mot de passe

            $manager->persist($testUser);
        }

        $manager->flush();
    }

    public function addTheme(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Récupérer l'admin pour l'associer aux thèmes
        $admin = $manager->getRepository(User::class)->findOneBy(['username' => 'admin']);

        for ($i = 0; $i < 5; $i++) {
            $theme = new Theme();
            $theme->setName($faker->words(3, true))
                ->setArchived($faker->boolean(30)) // 30% de chance d'être archivé
                ->setCreatedBy($admin); // Associer le thème à l'admin

            $manager->persist($theme);
        }
        $manager->flush();
    }

    public function addColor(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

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
            $card->setTitle($faker->sentence(4))
                ->setDetail($faker->paragraph())
                ->setMoreInfoTitle($faker->sentence(3))
                ->setMoreInfoDetails($faker->paragraph())
                ->setTheme($themes[array_rand($themes)]);
            $manager->persist($card);
        }
        $manager->flush();
    }
}