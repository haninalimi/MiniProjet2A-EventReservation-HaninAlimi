<?php

namespace App\Controller;

use App\Email\ReservationConfirmationEmail;
use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

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
            'events' => $this->eventRepository->findBy([], ['date' => 'ASC']),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $event = $this->eventRepository->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        return $this->render('event/show.html.twig', [
            'event'            => $event,
            'reservationCount' => $this->reservationRepository->countByEvent($event),
        ]);
    }

    #[Route('/{id}/reserve', name: 'reserve', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function reserve(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer         
    ): Response {
        $event = $this->eventRepository->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        $reservationCount = $this->reservationRepository->countByEvent($event);
        $availableSeats   = $event->getSeats() - $reservationCount;

        if ($availableSeats <= 0) {
            $this->addFlash('danger', 'Désolé, cet événement est complet.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $reservation = new Reservation();
        $form        = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($this->reservationRepository->hasAlreadyReserved($event, $reservation->getEmail())) {
                $this->addFlash('danger', 'Vous avez déjà réservé cet événement avec cet email.');
                return $this->redirectToRoute('event_reserve', ['id' => $event->getId()]);
            }

            $reservation->setEvent($event);
$reservation->setCreatedAt(new \DateTimeImmutable());
$em->persist($reservation);
$em->flush();

            try {
                $email = new ReservationConfirmationEmail($reservation);
                $mailer->send($email);
            } catch (\Exception $e) {
               
            }

            $this->addFlash('success', 'Réservation confirmée ! Un email de confirmation vous a été envoyé.');
            return $this->redirectToRoute('event_reservation_confirm', [
                'id' => $reservation->getId(),
            ]);
        }

        return $this->render('event/reserve.html.twig', [
            'event'          => $event,
            'form'           => $form,
            'availableSeats' => $availableSeats,
        ]);
    }

    #[Route('/reservation/{id}/confirm', name: 'reservation_confirm', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function confirm(int $id): Response
    {
        $reservation = $this->reservationRepository->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        return $this->render('event/confirm.html.twig', [
            'reservation' => $reservation,
        ]);
    }
    
}