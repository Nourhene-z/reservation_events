<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events')]
class EventController extends AbstractController
{
    #[Route('', name: 'event_list', methods: ['GET'])]
    public function list(EventRepository $events): Response
    {
        return $this->render('events/list.html.twig', [
            'events' => $events->findBy([], ['date' => 'ASC']),
        ]);
    }

    #[Route('/{id}', name: 'event_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function show(int $id, EventRepository $events): Response
    {
        $event = $events->find($id);
        if ($event === null) {
            throw $this->createNotFoundException('Event not found.');
        }

        return $this->render('events/show.html.twig', [
            'event' => $event,
        ]);
    }
}
