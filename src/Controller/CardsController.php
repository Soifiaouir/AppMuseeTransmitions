<?php

namespace App\Controller;

use App\Entity\Card;
use App\Form\CardType;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/cards', name: 'cards_')]
final class CardsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CardRepository $cardRepository)
    {}

    #[Route('/{page}', name: 'list', requirements: ['page' => '\d+'], methods: ['GET'])]
    public function list(int $page = 1): Response
    {
        $totalCards = $this->cardRepository->count();
        $maxPages = ceil($totalCards/ Card::CARD_PER_PAGE);

        if($page < 1) {
            return $this->redirectToRoute('cards_list', ['page' => 1]);
        }
        if($page > $maxPages) {
            return $this->redirectToRoute('cards_list', ['page' => $maxPages]);
        }
        $cards = $this->cardRepository->getCardByThemeWithPagination($page);

        return $this->render('cards/list.html.twig', [
            'cards' => $cards,
            'currentPage' => $page,
            'maxPages' => $maxPages,
        ]);
    }

    #[Route('/add', name: 'add', methods: ['GET', 'POST'])]
    public function add(Request $request): Response
    {
        $card = new Card();
        $formCard = $this->createForm(CardType::class, $card);
        $formCard->handleRequest($request);

        if ($formCard->isSubmitted() && $formCard->isValid()) {
            $this->em->persist($card);
            $this->em->flush();
            $this->addFlash('success', 'La carte '.$card->getTitle().' bien ajoutée au thème '.$card->getTheme()->getName().'.');

            return $this->redirectToRoute('cards_details', ['id' => $card->getId()]);
        }
        return $this->render('cards/add.html.twig', [
            'formCard' => $formCard,
        ]);
    }

    #[Route('/edit/{id}', name: 'edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(Request $request, int $id): Response
    {
        $card = $this->cardRepository->find($id);
        if (!$card) {
            throw $this->createNotFoundException('La carte n\'existe pas.');
        }

        $formCard = $this->createForm(CardType::class, $card);
        $formCard->handleRequest($request);

        if ($formCard->isSubmitted() && $formCard->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'La carte '.$card->getTitle().' a bien été modifiée');  // Correction ici

            return $this->redirectToRoute('cards_details', ['id' => $card->getId()]);
        }
        return $this->render('cards/edit.html.twig', [
            'card' => $card,
            'formCard' => $formCard,
        ]);
    }

    #[Route('/details/{id}', name: 'details', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function details(int $id): Response
    {
        $card = $this->cardRepository->findOneWithRelations($id);
        return $this->render('cards/details.html.twig', [
            'card' => $card,
        ]);
    }

    #[Route('/delete/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id): Response
    {
        $card = $this->cardRepository->find($id);
        if (!$card) {
            throw $this->createNotFoundException('La carte n\'existe pas.');
        }
        $this->em->remove($card);
        $this->em->flush();
        return $this->redirectToRoute('cards_list');
    }
}
