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
    }

    public function addUsers(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Admin - n'a pas besoin de changer son mot de passe
        $userAdmin = new User();
        $userAdmin->setUsername('admin')
            ->setPassword($this->userPasswordHasher->hashPassword($userAdmin, 'admin'))
            ->setRoles(['ROLE_ADMIN'])
            ->setPasswordChange(false)
            ->setPasswordChangeDate(new \DateTime());

        $manager->persist($userAdmin);

        $manager->flush();
    }
}