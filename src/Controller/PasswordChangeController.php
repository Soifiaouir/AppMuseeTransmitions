<?php
// src/Controller/PasswordChangeController.php

namespace App\Controller;

use App\Entity\User;
use App\Form\PasswordChangeType;
use App\Form\ResetPasswordAskType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PasswordChangeController extends AbstractController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface      $em,
        private readonly UserRepository              $userRepository,
    )
    {
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/change_password', name: 'change_password')]
    public function changePassword(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $formPasswordChange = $this->createForm(PasswordChangeType::class);
        $formPasswordChange->handleRequest($request);

        if ($formPasswordChange->isSubmitted() && $formPasswordChange->isValid()) {
            $newPassword = $formPasswordChange->get('newPassword')->getData();
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);

            $user->setPassword($hashedPassword);
            $user->setPasswordChange(false);
            $user->setPasswordChangeDate(new \DateTime());

            // IMPORTANT : Effacer le mot de passe temporaire après la première connexion réussie
            $user->setTempPassword(null);

            $this->em->flush();

            $this->addFlash('success', 'Votre mot de passe a été changé avec succès !');

            return $this->redirectToRoute('home');
        }

        return $this->render('security/change_password.html.twig', [
            'changePasswordForm' => $formPasswordChange,
            'mustChange' => $user->isPasswordChange(),
        ]);
    }

    #[Route('/password_reset_ask', name: 'password_reset_ask')]
    public function resetPasswordAsk(Request $request): Response
    {
        $formResetAsk = $this->createForm(ResetPasswordAskType::class);
        $formResetAsk->handleRequest($request);

        if ($formResetAsk->isSubmitted() && $formResetAsk->isValid()) {
            $username = $formResetAsk->get('username')->getData();
            $user = $this->userRepository->findOneBy(['username' => $username]);

            if($user) {
                // Pour forcer le changement de mot de passe
                $user->setPasswordChange(true);
                $this->em->flush();

                $this->addFlash('success', 'Votre demande a bien été enregistrée. Un administrateur vous communiquera un nouveau mot de passe temporaire.');
            } else {
                // Pour sécurité même message
                $this->addFlash('success', 'Votre demande a bien été enregistrée. Un administrateur vous communiquera un nouveau mot de passe temporaire.');
            }
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig', [
            'formResetAsk' => $formResetAsk,
        ]);
    }
}