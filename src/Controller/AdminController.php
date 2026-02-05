<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/', name: 'admin_')]
final class AdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository
    ) {
    }

    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(ThemeRepository $themeRepository): Response
    {
        $users = $this->userRepository->findAll();
        $themes = $themeRepository->findAll();

        $usersNeedingPasswordChange = $this->userRepository->count(['passwordChange' => true]);

        return $this->render('admin/dashboard.html.twig', [
            'users' => $users,
            'themes' => $themes,
            'usersNeedingPasswordChange' => $usersNeedingPasswordChange
        ]);
    }

    #[Route('/user/add', name: 'add_user', methods: ['GET', 'POST'])]
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

            // Stocker le mot de passe temporaire en clair pour que l'admin puisse le voir
            $user->setTempPassword($plainPassword);

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
                'L\'utilisateur %s a été créé avec succès. Le mot de passe temporaire est visible dans le tableau.',
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
    #[Route('/user/{id}/reset_password', name: 'reset_password', requirements: ['id' => '\d+'], methods: ['POST', 'PUT'])]
    public function reset_password(int $id): Response
    {
        $user = $this->userRepository->find($id);

        if(!$user) {
            $this->addFlash('error', 'Utilisateur introuvable');
            return $this->redirectToRoute('admin_dashboard');
        }

        // Générer un nouveau mot de passe temporaire
        $tempPassword = $this->generateTemporaryPassword();

        // Hasher et stocker le mot de passe
        $user->setPassword($this->passwordHasher->hashPassword($user, $tempPassword));

        // Stocker le mot de passe temporaire en clair pour que l'admin puisse le voir
        $user->setTempPassword($tempPassword);

        // Forcer le changement de mot de passe
        $user->setPasswordChange(true);
        $user->setPasswordChangeDate(null);

        $this->em->flush();

        $this->addFlash('success', 'Mot de passe temporaire réinitialisé.');
        $this->addFlash('warning', 'Nouveau mot de passe temporaire : '. $tempPassword);
        $this->addFlash('warning', 'Le mot de passe est également visible dans le tableau ci-dessous.');

        return $this->redirectToRoute('admin_dashboard');
    }

    /**
     * @throws RandomException
     */
    private function generateTemporaryPassword(): string
    {
        $password = '';
        $chars = 'azertyuiopqsdfghjklmwxcvbn789456123';

        for ($i = 0; $i < 12; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }

    #[Route('/user/{id}/remove', name: 'remove_user', requirements:['id' => '\d+'], methods: ['DELETE'])]
    public function removeUser(User $user): Response
    {
        $this->em->remove($user);
        $this->em->flush();

        $this->addFlash('success', sprintf('L\'utilisateur %s a été supprimé.', $user->getUsername()));

        return $this->redirectToRoute('admin_dashboard');
    }
}