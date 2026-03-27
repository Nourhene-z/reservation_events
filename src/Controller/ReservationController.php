<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ReservationController extends AbstractController
{
    #[Route('/events/{id}/reserve', name: 'reservation_create', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function create(
        int $id,
        Request $request,
        EventRepository $events,
        EntityManagerInterface $em,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('reserve_event_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid form token. Please try again.');
            return $this->redirectToRoute('event_show', ['id' => $id]);
        }

        $event = $events->find($id);
        if ($event === null) {
            throw $this->createNotFoundException('Event not found.');
        }

        $name = trim((string) $request->request->get('name'));
        $email = trim((string) $request->request->get('email'));
        $phone = trim((string) $request->request->get('phone'));

        if ($name === '' || $email === '' || $phone === '') {
            $this->addFlash('error', 'All fields are required.');
            return $this->redirectToRoute('event_show', ['id' => $id]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Please provide a valid email address.');
            return $this->redirectToRoute('event_show', ['id' => $id]);
        }

        if (($event->getSeats() ?? 0) <= 0) {
            $this->addFlash('error', 'No seats left for this event.');
            return $this->redirectToRoute('event_show', ['id' => $id]);
        }

        $reservation = new Reservation();
        $reservation
            ->setEvent($event)
            ->setName($name)
            ->setEmail($email)
            ->setPhone($phone)
            ->setCreatedAt(new \DateTime());

        $event->setSeats(max(0, (int) $event->getSeats() - 1));

        $em->persist($reservation);
        $em->flush();

        $this->addFlash('success', 'Reservation confirmed successfully.');

        return $this->redirectToRoute('event_show', ['id' => $id]);
    }
}
