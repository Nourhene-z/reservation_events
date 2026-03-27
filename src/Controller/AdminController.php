<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(EventRepository $events): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'events' => $events->findBy([], ['date' => 'ASC']),
        ]);
    }

    #[Route('/events/create', name: 'admin_event_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_event_create', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid form token.');
                return $this->redirectToRoute('admin_event_create');
            }

            try {
                $event = (new Event());
                $this->hydrateEventFromRequest($event, $request);

                $em->persist($event);
                $em->flush();
            } catch (\Throwable) {
                $this->addFlash('error', 'Please provide valid event data.');
                return $this->redirectToRoute('admin_event_create');
            }

            $this->addFlash('success', 'Event created successfully.');
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/event_form.html.twig', [
            'event' => null,
            'submitLabel' => 'Create event',
            'csrfTokenId' => 'admin_event_create',
        ]);
    }

    #[Route('/events/{id}/edit', name: 'admin_event_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(int $id, Request $request, EventRepository $events, EntityManagerInterface $em): Response
    {
        $event = $events->find($id);
        if ($event === null) {
            throw $this->createNotFoundException('Event not found.');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_event_edit_' . $id, (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid form token.');
                return $this->redirectToRoute('admin_event_edit', ['id' => $id]);
            }

            try {
                $this->hydrateEventFromRequest($event, $request);
                $em->flush();
            } catch (\Throwable) {
                $this->addFlash('error', 'Please provide valid event data.');
                return $this->redirectToRoute('admin_event_edit', ['id' => $id]);
            }

            $this->addFlash('success', 'Event updated successfully.');
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/event_form.html.twig', [
            'event' => $event,
            'submitLabel' => 'Update event',
            'csrfTokenId' => 'admin_event_edit_' . $id,
        ]);
    }

    #[Route('/events/{id}/delete', name: 'admin_event_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(int $id, Request $request, EventRepository $events, EntityManagerInterface $em): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('admin_event_delete_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid form token.');
            return $this->redirectToRoute('admin_dashboard');
        }

        $event = $events->find($id);
        if ($event !== null) {
            $em->remove($event);
            $em->flush();
            $this->addFlash('success', 'Event deleted successfully.');
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/events/{id}/reservations', name: 'admin_event_reservations', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function reservations(int $id, EventRepository $events): Response
    {
        $event = $events->find($id);
        if ($event === null) {
            throw $this->createNotFoundException('Event not found.');
        }

        return $this->render('admin/reservations.html.twig', [
            'event' => $event,
            'reservations' => $event->getReservations(),
        ]);
    }

    private function hydrateEventFromRequest(Event $event, Request $request): void
    {
        $title = trim((string) $request->request->get('title'));
        $description = trim((string) $request->request->get('description'));
        $date = trim((string) $request->request->get('date'));
        $location = trim((string) $request->request->get('location'));
        $seats = (int) $request->request->get('seats');
        $image = trim((string) $request->request->get('image'));

        if ($title === '' || $description === '' || $date === '' || $location === '' || $seats < 0) {
            throw new \InvalidArgumentException('Please provide valid event data.');
        }

        $event
            ->setTitle($title)
            ->setDescription($description)
            ->setDate(new \DateTime($date))
            ->setLocation($location)
            ->setSeats($seats)
            ->setImage($image !== '' ? $image : null);
    }
}
