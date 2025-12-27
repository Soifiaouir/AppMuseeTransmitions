<?php

namespace App\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/cards', name: 'cards_')]
final class CardsController extends AbstractController
{
    #[Route('/list', name: 'list')]
    public function list(): Response
    {
        return $this->render('cards/list.html.twig', [
        ]);
    }

    #[Route('/add', name: 'add')]
    public function add(): Response
    {
        return $this->render('cards/add.html.twig', [
        ]);
    }

    #[Route('/update/{id}', name: 'update', requirements: ['id' => '\d+'])]
    public function update(): Response
    {
        return $this->render('cards/update.html.twig', [
        ]);
    }

    #[Route('/details/{id}', name: 'details', requirements: ['id' => '\d+'])]
    public function details(): Response
    {
        return $this->render('cards/details.html.twig', [
        ]);
    }

    #[Route('/delete/{id}', name: 'update', requirements: ['id' => '\d+'])]
    public function delete(): Response
    {
        return $this->render('cards/delete.html.twig', [
        ]);
    }
}
