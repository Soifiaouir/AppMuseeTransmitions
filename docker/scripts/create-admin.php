<?php

require '/var/www/html/vendor/autoload.php';

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

$kernel = new \App\Kernel('prod', false);
$kernel->boot();
$container = $kernel->getContainer();

/** @var EntityManagerInterface $em */
$em = $container->get('doctrine')->getManager();

/** @var UserPasswordHasherInterface $hasher */
$hasher = $container->get(UserPasswordHasherInterface::class);

// Chercher si l'utilisateur existe déjà
$userRepo = $em->getRepository(User::class);
$user = $userRepo->findOneBy(['username' => 'soifia']);

if (!$user) {
    $user = new User();
    $user->setUsername('soifia');
    echo "Création du nouvel utilisateur 'soifia'...\n";
} else {
    echo "Mise à jour de l'utilisateur 'soifia'...\n";
}

$user->setPassword($hasher->hashPassword($user, '123456789Azerty'))
    ->setRoles(['ROLE_ADMIN'])
    ->setPasswordChange(false)
    ->setPasswordChangeDate(new \DateTime());

$em->persist($user);
$em->flush();

echo "✅ Utilisateur 'soifia' créé/mis à jour avec succès !\n";
echo "   Username: soifia\n";
echo "   Password: 123456789Azerty\n";
echo "   Rôle: ROLE_ADMIN\n";