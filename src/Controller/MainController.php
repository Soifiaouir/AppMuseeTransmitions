<?php

namespace App\Controller;

use App\Entity\Theme;
use App\Form\ThemeType;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    public function __construct(private readonly ThemeRepository        $themeRepository,
                                private readonly EntityManagerInterface $em,)
    {
    }

    #[Route('/', name: 'home')]
    public function home(): Response
    {
        $themes =$this->themeRepository->findBy(['archived' => false], ['dateOfCreation' => 'DESC']);

        return $this->render('main/home.html.twig', [
            'themes' => $themes,
        ]);
    }

    #[Route('/theme/add', name: 'add_theme')]
    public function add(Request $request): Response
    {
        $theme = new Theme();
        $formTheme = $this->createForm(ThemeType::class, $theme);
        $formTheme->handleRequest($request);

        if ($formTheme->isSubmitted() && $formTheme->isValid()) {
            $this->em->persist($theme);
            $this->em->flush();

            $this->addFlash('success', 'Le thème '.$theme->getName(), ' a bien été créer');
            return $this->redirectToRoute('home');
        }

        return $this->render('main/add_theme.html.twig', [
            'formTheme' => $formTheme,
        ]);
    }

    #[Route('/theme/edit/{id}', name: 'edit_theme', requirements: ['id' => '\d+'])]
    public function edit(Theme $theme, Request $request, ): Response
    {
        if ($theme->isArchived()) {
            $this->addFlash('warning', 'Impossible de modifier un thème archivé.');
            return $this->redirectToRoute('home');
        }

        $formTheme = $this->createForm(ThemeType::class, $theme);
        $formTheme->handleRequest($request);

        if ($formTheme->isSubmitted() && $formTheme->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Thème '.$theme->getName(). ' modifié avec succès !');
            return $this->redirectToRoute('home');
        }

        return $this->render('main/edit_theme.html.twig', [
            'formTheme' => $formTheme,
            'theme' => $theme,
        ]);
    }

    #[Route('/theme/details/{id}', name: 'details_theme', requirements: ['id' => '\d+'])]
    public function details(int $id): Response
    {
        $theme = $this->themeRepository->find($id);
        return $this->render('main/details_theme.html.twig', [
            'theme' => $theme,
        ]);
    }

    /**
     * @param int $id
     * @return Response
     * Seul un admin est autoriser à supprimer un thème
     */
    #[Route('/theme/delete/{id}', name: 'delete_theme', requirements: ['id' => '\d+'])]
    public function delete(Theme $theme): Response
    {
    $this->em->remove($theme);
    $this->em->flush();
    $this->addFlash('success', 'Theme '.$theme->getName().' bien supprimer.');
                return $this->redirectToRoute('home');
    }
}
