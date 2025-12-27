<?php

namespace App\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/media', name: 'media')]
final class MediaController extends AbstractController
{
     #[Route('/list', name: 'list')]
    public function list(): Response
    {
        return $this->render('media/list.html.twig', [

        ]);
    }

    #[Route('/delete/{id}', name: 'update', requirements: ['id' => '\d+'])]
    public function delete(): Response
    {
        return $this->render('media/delete.html.twig', [
        ]);
    }
}
