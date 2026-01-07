<?php
// src/Controller/PasswordChangeController.php

namespace App\Controller;

use App\Entity\User;
use App\Form\PasswordChangeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class PasswordChangeController extends AbstractController
{
    #[Route('/change-password', name: 'app_change_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(PasswordChangeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);

            $user->setPassword($hashedPassword);
            $user->setPasswordChange(false);
            $user->setPasswordChangeDate(new \DateTime());

            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été changé avec succès !');

            return $this->redirectToRoute('home');
        }

        return $this->render('password_change/change_password.html.twig', [
            'changePasswordForm' => $form,
            'mustChange' => $user->isPasswordChange(),
        ]);
    }
}