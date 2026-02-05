<?php

namespace App\Controller;

use App\Entity\Color;
use App\Form\ColorType;
use App\Repository\ColorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controleur en charge de gerer le lien entre la base de donnée et les vues lié à l'entité color
 *Le but est de renforcé le theme en y ajoutant de couleurs qui porron ensuite etre utiliser par le musée lors de la créations de differents
 *pages liés aux expositions en proposant une thématique complete.
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/color', name: 'color_')]
final class ColorController extends AbstractController
{
    public function __construct(private readonly ColorRepository        $colorRepository,
                                private readonly EntityManagerInterface $em,)
    {
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        $colors = $this->colorRepository->findAll();
        return $this->render('color/list.html.twig', [
            'colors' => $colors,
        ]);
    }
    #[Route('/add', name: 'add', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $color = new Color();
        $formColor = $this->createForm(ColorType::class, $color);
        $formColor->handleRequest($request);

        if ($formColor->isSubmitted() && $formColor->isValid()) {
            $this->em->persist($color);
            $this->em->flush();
            $this->addFlash('success', 'La couleur a bien été crée.');
            return $this->redirectToRoute('color_list');
        }
        return $this->render('color/add.html.twig', [
            'formColor' => $formColor,
        ]);
    }
    #[Route('/edit/{id}', name: 'edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(Request $request, int $id): Response
    {
        $color = $this->colorRepository->find($id);
        $formColor = $this->createForm(ColorType::class, $color);
        $formColor->handleRequest($request);

        if ($formColor->isSubmitted() && $formColor->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'La couleur à bien été mise à jours');
            return $this->redirectToRoute('color_list');
        }
        return $this->render('color/edit.html.twig', [
            'color' => $color,
            'formColor' => $formColor
        ]);
    }
    #[Route('/remove', name: 'remove', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function remove(int $id): Response
    {
        $colors = $this->colorRepository->find($id);
        if (!$colors) {
            throw $this->createNotFoundException('La couleur n\'existe pas.');
        }
        $colors->remove();
        $this->em->flush();
        $this->addFlash('success', 'La couleur à bien été supprimée');
        return $this->redirectToRoute('color_list');
    }
}
