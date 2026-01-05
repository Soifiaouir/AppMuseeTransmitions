<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\EmailVerifier;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Repository\ThemeRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;



#[Route('/', name: 'admin_')]

final class AdminController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em,
    private readonly UserPasswordHasherInterface $passwordHasher,
    private readonly UserRepository $userRepository,
                                private EmailVerifier $emailVerifier){}

    #[Route('/dashboard', name: 'dashboard')]
    public function dashbord(ThemeRepository $themeRepository): Response
    {
        $users = $this->userRepository->findAll();
        $themes = $themeRepository->findAll();
        return $this->render('admin/dashboard.html.twig',
            [
                'users' => $users,
                'themes' => $themes
            ]);
    }
    #[Route('/user/add', name: 'add_user')]
    public function addUser(Request $request, Security $security): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Encoder le mot de passe
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

            $this->em->persist($user);
            $this->em->flush();

            // Envoyer l'email de confirmation
            $this->emailVerifier->sendEmailConfirmation(
                'admin_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('soifiaouir@gmail.com', 'Ouirdane Soifia'))
                    ->to((string) $user->getEmail())
                    ->subject('Confirmez votre email s\'il vous plaît')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            $this->addFlash('success', 'L\'utilisateur a été créé avec succès. Un email de confirmation a été envoyé.');

            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/add_user.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $id = $request->query->get('id');

        if (null === $id) {
            return $this->redirectToRoute('add_user');
        }

        $user = $this->userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('add_user');
        }

        // Valider le lien de confirmation d'email
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('add_user');
        }

        $this->addFlash('success', 'L\'email a été vérifié avec succès.');

        return $this->redirectToRoute('home');
    }

}
