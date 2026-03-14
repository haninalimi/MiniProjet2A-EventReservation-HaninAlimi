<?php

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
class AdminController extends AbstractController
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly ReservationRepository $reservationRepository,
        private readonly SluggerInterface $slugger,
    ) {}

    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'totalEvents'       => $this->eventRepository->count([]),
            'totalReservations' => $this->reservationRepository->count([]),
            'latestEvents'      => $this->eventRepository->findBy([], ['id' => 'DESC'], 5),
        ]);
    }

    #[Route('/events', name: 'event_index', methods: ['GET'])]
    public function eventIndex(): Response
    {
        return $this->render('admin/event/index.html.twig', [
            'events' => $this->eventRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/events/new', name: 'event_new', methods: ['GET', 'POST'])]
    public function eventNew(Request $request): Response
    {
        $event = new Event();
        $form  = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $event);
            $this->eventRepository->save($event, true);
            $this->addFlash('success', 'Événement créé avec succès !');
            return $this->redirectToRoute('admin_event_index');
        }

        return $this->render('admin/event/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/events/{id}/edit', name: 'event_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function eventEdit(Request $request, Event $event): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $event);
            $this->eventRepository->save($event, true);
            $this->addFlash('success', 'Événement modifié avec succès !');
            return $this->redirectToRoute('admin_event_index');
        }

        return $this->render('admin/event/edit.html.twig', [
            'form'  => $form,
            'event' => $event,
        ]);
    }

    #[Route('/events/{id}/delete', name: 'event_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function eventDelete(Request $request, Event $event): Response
    {
        if ($this->isCsrfTokenValid('delete_event_' . $event->getId(), $request->request->get('_token'))) {
            $this->eventRepository->remove($event, true);
            $this->addFlash('success', 'Événement supprimé.');
        }

        return $this->redirectToRoute('admin_event_index');
    }

  
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


    private function handleImageUpload($form, Event $event): void
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