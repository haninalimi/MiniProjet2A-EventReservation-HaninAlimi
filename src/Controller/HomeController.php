<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'featuredEvents' => $eventRepository->findBy([], ['id' => 'DESC'], 3),
            'totalEvents'    => $eventRepository->count([]),
        ]);
    }
}