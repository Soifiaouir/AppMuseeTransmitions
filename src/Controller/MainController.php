<?php

namespace App\Controller;

use App\Entity\Media;
use App\Entity\Theme;
use App\Form\ThemeType;
use App\Repository\ThemeRepository;
use App\Service\MediaUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class MainController extends AbstractController
{
    public function __construct(private readonly ThemeRepository        $themeRepository,
                                private readonly EntityManagerInterface $em,)
    {
    }

    #[Route('/', name: 'home')]
    #[Route('/{page<\d+>}', name: 'home_page', requirements: ['page' => '\d+'])]
    public function home(int $page = 1): Response
    {
        $totalTheme = $this->themeRepository->count();
        $maxPages = ($totalTheme > 0) ? ceil($totalTheme / Theme::THEME_PER_PAGE) : 1;

        if ($page < 1) {
            return $this->redirectToRoute('home');
        }
        if ($page > $maxPages && $maxPages > 0) {
            return $this->redirectToRoute('home');
        }

        $paginator = $this->themeRepository->findALLWithPagination($page);
        $themes = iterator_to_array($paginator); // Ajoutez cette ligne

        return $this->render('main/home.html.twig', [
            'themes' => $themes,
            'currentPage' => $page,
            'maxPages' => $maxPages,
        ]);
    }


    #[Route('/theme/add', name: 'add_theme')]
    public function add(Request $request, MediaUploader $uploader): Response
    {
        $theme = new Theme();
        $formTheme = $this->createForm(ThemeType::class, $theme, [
            'is_admin' => $this->isGranted('ROLE_ADMIN')
        ]);
        $formTheme->handleRequest($request);

        if ($formTheme->isSubmitted() && $formTheme->isValid()) {
            try {
                $theme->setCreatedBy($this->getUser());

                // 1. Gérer l'image de fond principale
                $backgroundImageFile = $formTheme->get('backgroundImageFile')->getData();
                if ($backgroundImageFile) {
                    $media = new Media();

                    // IMPORTANT : Remplir les champs obligatoires AVANT le flush
                    $media->setUserGivenName($backgroundImageFile->getClientOriginalName());
                    $media->setName('temp'); // Temporaire, sera remplacé par l'ID
                    $media->setType('temp'); // Temporaire, sera détecté par l'uploader
                    $media->setExtensionFile('tmp'); // Temporaire, sera détecté par l'uploader

                    // Persister pour obtenir l'ID
                    $this->em->persist($media);
                    $this->em->flush();

                    // Upload le fichier (remplit name, type, extensionFile avec les vraies valeurs)
                    $uploader->upload($backgroundImageFile, $media);

                    // Associer au thème
                    $theme->setBackgroundImage($media);
                }

                // 2. Gérer les médias multiples
                $mediaFiles = $formTheme->get('mediaFiles')->getData();
                if ($mediaFiles) {
                    foreach ($mediaFiles as $file) {
                        $media = new Media();

                        // IMPORTANT : Remplir les champs obligatoires AVANT le flush
                        $media->setUserGivenName($file->getClientOriginalName());
                        $media->setName('temp'); // Temporaire, sera remplacé par l'ID
                        $media->setType('temp'); // Temporaire, sera détecté par l'uploader
                        $media->setExtensionFile('tmp'); // Temporaire, sera détecté par l'uploader

                        // Persister pour obtenir l'ID
                        $this->em->persist($media);
                        $this->em->flush();

                        // Upload le fichier (remplit name, type, extensionFile avec les vraies valeurs)
                        $uploader->upload($file, $media);

                        // Associer le média au thème
                        $theme->addMedia($media);
                    }
                }

                // 3. Sauvegarder le thème et tous les médias avec leurs vraies valeurs
                $this->em->persist($theme);
                $this->em->flush();

                $this->addFlash('success', 'Le thème ' . $theme->getName() . ' a bien été créé avec ses médias');
                return $this->redirectToRoute('home');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
            }
        }

        return $this->render('main/add_theme.html.twig', [
            'formTheme' => $formTheme,
        ]);
    }

    #[Route('/theme/edit/{id}', name: 'edit_theme', requirements: ['id' => '\d+'])]
    public function edit(Theme $theme, Request $request, MediaUploader $uploader): Response
    {
        if ($theme->isArchived()) {
            $this->addFlash('warning', 'Impossible de modifier un thème archivé.');
            return $this->redirectToRoute('home');
        }

        $formTheme = $this->createForm(ThemeType::class, $theme, [
            'is_admin' => $this->isGranted('ROLE_ADMIN')
        ]);
        $formTheme->handleRequest($request);

        if ($formTheme->isSubmitted() && $formTheme->isValid()) {
            try {
                // 1. Gérer l'image de fond principale
                $backgroundImageFile = $formTheme->get('backgroundImageFile')->getData();
                if ($backgroundImageFile) {
                    $media = new Media();

                    // IMPORTANT : Remplir les champs obligatoires AVANT le flush
                    $media->setUserGivenName($backgroundImageFile->getClientOriginalName());
                    $media->setName('temp'); // Temporaire, sera remplacé par l'ID
                    $media->setType('temp'); // Temporaire, sera détecté par l'uploader
                    $media->setExtensionFile('tmp'); // Temporaire, sera détecté par l'uploader

                    // Persister pour obtenir l'ID
                    $this->em->persist($media);
                    $this->em->flush();

                    // Upload le fichier (remplit name, type, extensionFile avec les vraies valeurs)
                    $uploader->upload($backgroundImageFile, $media);

                    // Associer au thème (remplace l'ancienne image)
                    $theme->setBackgroundImage($media);
                }

                // 2. Gérer les nouveaux médias multiples
                $mediaFiles = $formTheme->get('mediaFiles')->getData();
                if ($mediaFiles) {
                    foreach ($mediaFiles as $file) {
                        $media = new Media();

                        // IMPORTANT : Remplir les champs obligatoires AVANT le flush
                        $media->setUserGivenName($file->getClientOriginalName());
                        $media->setName('temp'); // Temporaire, sera remplacé par l'ID
                        $media->setType('temp'); // Temporaire, sera détecté par l'uploader
                        $media->setExtensionFile('tmp'); // Temporaire, sera détecté par l'uploader

                        // Persister pour obtenir l'ID
                        $this->em->persist($media);
                        $this->em->flush();

                        // Upload le fichier (remplit name, type, extensionFile avec les vraies valeurs)
                        $uploader->upload($file, $media);

                        // Ajouter le média au thème (s'ajoute aux existants)
                        $theme->addMedia($media);
                    }
                }

                // 3. Sauvegarder les modifications
                $this->em->flush();

                $this->addFlash('success', 'Thème ' . $theme->getName() . ' modifié avec succès !');
                return $this->redirectToRoute('home');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
            }
        }

        return $this->render('main/edit_theme.html.twig', [
            'formTheme' => $formTheme,
            'theme' => $theme,
        ]);
    }


    #[Route('/theme/details/{id}', name: 'details_theme', requirements: ['id' => '\d+'])]
    public function details(int $id): Response
    {
        $theme = $this->themeRepository->findOneWithRelations($id);
        return $this->render('main/details_theme.html.twig', [
            'theme' => $theme,
        ]);
    }

    /**
     * Seul un admin est autorisé à supprimer un thème
     */
    #[Route('/theme/remove/{id}', name: 'remove_theme', requirements: ['id' => '\d+'])]
    public function remove(Theme $theme): Response
    {
        $this->em->remove($theme);
        $this->em->flush();
        $this->addFlash('success', 'Thème '.$theme->getName().' bien supprimé.');
        return $this->redirectToRoute('home');
    }

    #[Route('/theme/archive/{id}', name: 'archive_theme', requirements: ['id' => '\d+'])]
    public function archive(Theme $theme): Response
    {
        // Le ThemeArchiveListener va automatiquement archiver les cartes et médias liés
        $theme->setArchived(true);
        $this->em->persist($theme);
        $this->em->flush();

        $this->addFlash('success', 'Thème archivé avec succès (ainsi que ses cartes et médias).');
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/theme/un_archive/{id}', name: 'unarchive_theme', requirements: ['id' => '\d+'])]
    public function unarchive(Theme $theme): Response
    {
        // Le ThemeArchiveListener va automatiquement désarchiver les cartes et médias liés
        $theme->setArchived(false);
        $this->em->persist($theme);
        $this->em->flush();

        $this->addFlash('success', 'Thème désarchivé avec succès (ainsi que ses cartes et médias).');
        return $this->redirectToRoute('admin_dashboard');
    }
}