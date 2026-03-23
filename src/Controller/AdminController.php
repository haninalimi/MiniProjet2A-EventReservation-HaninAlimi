<?php
// src/Controller/AdminController.php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly ReservationRepository $reservationRepository,
        private readonly SluggerInterface $slugger,
    ) {}

    /* ─── DASHBOARD ─────────────────────────────────────────── */
    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'totalEvents'       => $this->eventRepository->count([]),
            'totalReservations' => $this->reservationRepository->count([]),
            'latestEvents'      => $this->eventRepository->findBy([], ['id' => 'DESC'], 5),
        ]);
    }

    /* ─── EVENT INDEX — passe les 2 forms pour les modals ───── */
    #[Route('/events', name: 'event_index', methods: ['GET'])]
    public function eventIndex(): Response
    {
        $formNew = $this->createForm(EventType::class, new Event(), [
            'action' => $this->generateUrl('admin_event_new'),
        ]);

        return $this->render('admin/event/index.html.twig', [
            'events'    => $this->eventRepository->findBy([], ['id' => 'DESC']),
            'formNew'   => $formNew,
            'formEdit'  => null,
            'editEvent' => null,
        ]);
    }

    /* ─── EVENT NEW ─────────────────────────────────────────── */
    #[Route('/events/new', name: 'event_new', methods: ['GET', 'POST'])]
    public function eventNew(Request $request): Response
    {
        $event = new Event();
        $form  = $this->createForm(EventType::class, $event, [
            'action' => $this->generateUrl('admin_event_new'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $event);
            $this->eventRepository->save($event, true);
            $this->addFlash('success', 'Événement créé avec succès !');
            return $this->redirectToRoute('admin_event_index');
        }

        // Erreur → réafficher index avec modal new ouvert
        $formNew = $this->createForm(EventType::class, new Event(), [
            'action' => $this->generateUrl('admin_event_new'),
        ]);

        return $this->render('admin/event/index.html.twig', [
            'events'      => $this->eventRepository->findBy([], ['id' => 'DESC']),
            'formNew'     => $form,
            'formNewOpen' => true,
            'formEdit'    => null,
            'editEvent'   => null,
        ]);
    }

    /* ─── EVENT EDIT ─────────────────────────────────────────── */
    #[Route('/events/{id}/edit', name: 'event_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function eventEdit(Request $request, Event $event): Response
    {
        $form = $this->createForm(EventType::class, $event, [
            'action' => $this->generateUrl('admin_event_edit', ['id' => $event->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $event);
            $this->eventRepository->save($event, true);
            $this->addFlash('success', 'Événement modifié avec succès !');
            return $this->redirectToRoute('admin_event_index');
        }

        $formNew = $this->createForm(EventType::class, new Event(), [
            'action' => $this->generateUrl('admin_event_new'),
        ]);

        return $this->render('admin/event/index.html.twig', [
            'events'       => $this->eventRepository->findBy([], ['id' => 'DESC']),
            'formNew'      => $formNew,
            'formEdit'     => $form,
            'formEditOpen' => true,
            'editEvent'    => $event,
        ]);
    }

    /* ─── EVENT DELETE ──────────────────────────────────────── */
    #[Route('/events/{id}/delete', name: 'event_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function eventDelete(Request $request, Event $event): Response
    {
        if ($this->isCsrfTokenValid('delete_event_' . $event->getId(), $request->request->get('_token'))) {
            $this->eventRepository->remove($event, true);
            $this->addFlash('success', 'Événement supprimé avec succès.');
        }
        return $this->redirectToRoute('admin_event_index');
    }

    /* ─── RESERVATIONS ──────────────────────────────────────── */
    #[Route('/events/{id}/reservations', name: 'event_reservations', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function eventReservations(Event $event): Response
    {
        return $this->render('admin/reservation/index.html.twig', [
            'event'        => $event,
            'reservations' => $this->reservationRepository->findBy(
                ['event' => $event],
                ['createdAt' => 'DESC']
            ),
        ]);
    }

    /* ─── UPLOAD IMAGE ──────────────────────────────────────── */
    private function handleImageUpload(mixed $form, Event $event): void
    {
        $imageFile = $form->get('imageFile')->getData();
        if (!$imageFile) return;

        $safeFilename = $this->slugger->slug(
            pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME)
        );
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

        $imageFile->move(
            $this->getParameter('kernel.project_dir') . '/public/uploads/events',
            $newFilename
        );

        $event->setImage($newFilename);
    }
}
