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

#[Route('/cards', name: 'cards_')]
final class CardsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CardRepository $cardRepository)
    {}

    #[Route('/', name: 'list')]
    public function list(): Response
    {
        $cards = $this->cardRepository->findAll();
        return $this->render('cards/list.html.twig', [
            'cards' => $cards,
        ]);
    }

    #[Route('/add', name: 'add')]
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

    #[Route('/edit/{id}', name: 'edit', requirements: ['id' => '\d+'])]
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
            $this>$this->addFlash('success', 'La carte '.$card->getTitle().' a bien été modifiée');

            return $this->redirectToRoute('cards_details', ['id' => $card->getId()]);
        }
        return $this->render('cards/edit.html.twig', [
            'card' => $card,
            'formCard' => $formCard,
        ]);
    }

    #[Route('/details/{id}', name: 'details', requirements: ['id' => '\d+'])]
    public function details(int $id): Response
    {
        $card = $this->cardRepository->find($id);
        return $this->render('cards/details.html.twig', [
            'card' => $card,
        ]);
    }

    #[Route('/delete/{id}', name: 'delete', requirements: ['id' => '\d+'])]
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
