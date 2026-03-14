<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/events', name: 'event_')]
class EventController extends AbstractController
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly ReservationRepository $reservationRepository,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('event/index.html.twig', [
            'events' => $this->eventRepository->findBy(
                ['date' => null],
                ['date' => 'ASC']
            ) ?: $this->eventRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
            'reservationCount' => $this->reservationRepository->count(['event' => $event]),
        ]);
    }

    #[Route('/{id}/reserve', name: 'reserve', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function reserve(Request $request, Event $event): Response
    {
        $availableSeats = $event->getSeats() - $this->reservationRepository->count(['event' => $event]);

        if ($availableSeats <= 0) {
            $this->addFlash('danger', 'Désolé, cet événement est complet.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $reservation = new Reservation();
        $reservation->setEvent($event);
        $reservation->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->reservationRepository->save($reservation, true);
            $this->addFlash('success', 'Réservation confirmée ! Vous recevrez une confirmation par email.');
            return $this->redirectToRoute('event_reservation_confirm', ['id' => $reservation->getId()]);
        }

        return $this->render('event/reserve.html.twig', [
            'event'          => $event,
            'form'           => $form,
            'availableSeats' => $availableSeats,
        ]);
    }

    #[Route('/reservation/{id}/confirm', name: 'reservation_confirm', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function confirm(Reservation $reservation): Response
    {
        return $this->render('event/confirm.html.twig', [
            'reservation' => $reservation,
        ]);
    }
}