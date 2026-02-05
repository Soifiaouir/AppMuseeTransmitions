<?php
// src/Controller/MediaController.php

namespace App\Controller;

use App\Entity\Media;
use App\Form\MediaType;
use App\Repository\MediaRepository;
use App\Service\MediaUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/media', name: 'media_')]
final class MediaController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MediaRepository        $mediaRepository,
        private readonly MediaUploader          $uploader
    ) {
    }

    #[Route('/{page}', name: 'list', requirements: ['page' => '\d+'], methods: ['GET'])]
    public function list(int $page = 1): Response
    {
        $totalMedia = $this->mediaRepository->count();
        $maxPages = ceil($totalMedia/ Media::MEDIA_PER_PAGE);
        if($page < 1) {
            return $this->redirectToRoute('cards_list', ['page' => 1]);
        }
        if($page > $maxPages) {
            return $this->redirectToRoute('cards_list', ['page' => $maxPages]);
        }
        $medias = $this->mediaRepository->getMediaByThemeWithPagination($page);

        return $this->render('media/list.html.twig', [
            'medias' => $medias,
            'currentPage' => $page,
            'maxPages' => $maxPages,

        ]);
    }

    #[Route('/add', name: 'add', methods: ['GET', 'POST'])]
    public function add(Request $request): Response
    {
        $media = new Media();
        $formMedia = $this->createForm(MediaType::class, $media);
        $formMedia->handleRequest($request);

        if ($formMedia->isSubmitted() && $formMedia->isValid()) {
            $file = $formMedia->get('file')->getData();

            if ($file) {
                // Vérifier si le fichier existe déjà
                $userGivenName = $media->getUserGivenName() ?: $file->getClientOriginalName();

                if ($this->uploader->isDuplicate($file, $userGivenName)) {
                    $this->addFlash('error', 'Ce fichier existe déjà dans la base de données.');

                    return $this->render('media/add.html.twig', [
                        'formMedia' => $formMedia,
                        'media' => $media,
                    ]);
                }

                // Initialiser les champs obligatoires AVANT le premier flush
                $media->setName('temp'); // Valeur temporaire
                $media->setType('temp'); // Valeur temporaire
                $media->setExtensionFile('tmp'); // Valeur temporaire

                // ÉTAPE 1 : Persister pour obtenir un ID
                $this->em->persist($media);
                $this->em->flush();

                // ÉTAPE 2 : Upload le fichier (remplace les valeurs temp par les vraies)
                $this->uploader->upload($file, $media);

                // ÉTAPE 3 : Sauvegarder les vraies métadonnées
                $this->em->flush();

                $this->addFlash('success', 'Le média a été ajouté avec succès.');

                return $this->redirectToRoute('media_list');
            }
        }

        return $this->render('media/add.html.twig', [
            'formMedia' => $formMedia,
            'media' => $media,
        ]);
    }

    #[Route('/detail/{id}', name: 'detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function details(Media $media): Response
    {
        return $this->render('media/details.html.twig', [
            'media' => $media,
        ]);
    }

    #[Route('/edit/{id}', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Media $media): Response
    {
        $formMedia = $this->createForm(MediaType::class, $media);
        $formMedia->remove('file');
        $formMedia->remove('type');

        $formMedia->handleRequest($request);

        if ($formMedia->isSubmitted() && $formMedia->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Le média a été modifié avec succès.');

            return $this->redirectToRoute('media_detail', ['id' => $media->getId()]);
        }

        return $this->render('media/edit.html.twig', [
            'media' => $media,
            'formMedia' => $formMedia
        ]);
    }

    #[Route('/remove/{id}', name: 'remove', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function remove(int $id): Response
    {
        $media = $this->em->getRepository(Media::class)->find($id);

        if ($media) {
            // Supprimer le fichier physique
            $this->uploader->delete($media);

            // Supprimer l'entité
            $this->em->remove($media);
            $this->em->flush();

            $this->addFlash('success', 'Le média a bien été supprimé.');
        }

        return $this->redirectToRoute('media_list');
    }

}