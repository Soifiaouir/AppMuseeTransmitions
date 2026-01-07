<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'admin_')]
final class AdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository
    ) {
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashbord(ThemeRepository $themeRepository): Response
    {
        $users = $this->userRepository->findAll();
        $themes = $themeRepository->findAll();

        return $this->render('admin/dashboard.html.twig', [
            'users' => $users,
            'themes' => $themes
        ]);
    }

    #[Route('/user/add', name: 'add_user')]
    public function addUser(Request $request): Response
    {
        $user = new User();
        $formRegistration = $this->createForm(RegistrationFormType::class, $user);
        $formRegistration->handleRequest($request);

        if ($formRegistration->isSubmitted() && $formRegistration->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $formRegistration->get('plainPassword')->getData();

            // Encoder le mot de passe temporaire
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

            // Gérer les rôles
            $roles = $formRegistration->get('roles')->getData();

            if (is_array($roles) && !empty($roles)) {
                $user->setRoles($roles);
            } else {
                // Si aucun rôle n'est coché, mettre ROLE_USER par défaut
                $user->setRoles(['ROLE_USER']);
            }

            // IMPORTANT : L'utilisateur DOIT changer son mot de passe à la première connexion
            $user->setPasswordChange(true);
            $user->setPasswordChangeDate(null); // Pas encore de changement de mot de passe

            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash('success', sprintf(
                'L\'utilisateur %s a été créé avec succès. Il devra changer son mot de passe à la première connexion.',
                $user->getUsername()
            ));
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/add_user.html.twig', [
            'registrationForm' => $formRegistration,
        ]);
    }

    /**
     * @throws RandomException
     */
    #[Route('/user/{id}/reset-password', name: 'reset_user_password', methods: ['POST'])]
    public function resetUserPassword(User $user, LoggerInterface $logger): Response
    {
        // Générer un mot de passe temporaire simple
        $temporaryPassword = 'temp' . random_int(1000, 9999);

        // Hasher le mot de passe temporaire
        $user->setPassword($this->passwordHasher->hashPassword($user, $temporaryPassword));
        $user->setPasswordChange(true);
        $user->setPasswordChangeDate(null);

        $this->em->flush();

        $this->addFlash('success', sprintf(
            'Le mot de passe de l\'utilisateur %s a été réinitialisé. Nouveau mot de passe temporaire : <strong>%s</strong>',
            $user->getUsername(),
            $temporaryPassword
        ));

        $logger->info('Mot de passe réinitialisé par admin', [
            'username' => $user->getUsername(),
            'reset_by' => $this->getUser()?->getUserIdentifier()
        ]);

        return $this->redirectToRoute('admin_dashboard');
    }

//    #[Route('/user/{id}/delete', name: 'delete_user', methods: ['POST'])]
//    public function deleteUser(User $user, Request $request): Response
//    {
//        // Vérification du token CSRF
//        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
//            $username = $user->getUsername();
//
//            $this->em->remove($user);
//            $this->em->flush();
//
//            $this->addFlash('success', sprintf('L\'utilisateur %s a été supprimé.', $username));
//        } else {
//            $this->addFlash('error', 'Token CSRF invalide.');
//        }
//
//        return $this->redirectToRoute('admin_dashboard');
//    }
}