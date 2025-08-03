<?php

namespace App\Controller\Api;
// src/Controller/CalendarController.php
use App\Repository\SeanceRepository;
use App\Repository\EvenementRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/calendar')]
class CalenderController extends AbstractController
{
    public function __construct(
        private SeanceRepository $seanceRepository,
        private EvenementRepository $evenementRepository
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Tag(name: 'calendar')]
    public function getCalendarEvents(Request $request): JsonResponse
    {
        $start = $request->query->get('start');
        $end = $request->query->get('end');
        $classeId = $request->query->get('classe');
        $teacherId = $request->query->get('teacher');

        // Récupérer les séances
        $seances = $this->seanceRepository->findBetweenDates(
            new \DateTime($start),
            new \DateTime($end),
            $classeId,
            $teacherId
        );

        // Récupérer les événements
        $evenements = $this->evenementRepository->findBetweenDates(
            new \DateTime($start),
            new \DateTime($end)
        );

        // Formatter pour FullCalendar
        $events = [];
        
        foreach ($seances as $seance) {
            $events[] = [
                'id' => 'seance_' . $seance->getId(),
                'title' => $seance->getType(),
                'start' => $seance->getDate()->format('c'),
                'type' => 'seance',
                'className' => 'seance-' . $seance->getType()
            ];
        }

        foreach ($evenements as $evenement) {
            $events[] = [
                'id' => 'event_' . $evenement->getId(),
                'title' => $evenement->getTitre(),
                'start' => $evenement->getDate()->format('c'),
                'type' => 'evenement',
                'className' => 'evenement'
            ];
        }

        return $this->json($events);
    }
}