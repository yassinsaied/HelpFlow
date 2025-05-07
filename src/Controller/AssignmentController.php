<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Ticket;
use App\Entity\Enum\TicketStatus;
use App\Service\NotificationService;
use App\Entity\Enum\TechnicianStatus;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\Api\IriConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsController]
class AssignmentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private IriConverterInterface $iriConverter,
        private NotificationService $notificationService
    ) {}

    
    public function __invoke(Ticket $ticket, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $technicianIri = $data['assignedTo'] ?? null;

        if (!$technicianIri) {
            throw new \InvalidArgumentException('assignedTo is required');
        }

        try {
            $technician = $this->iriConverter->getResourceFromIri($technicianIri);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => 'Invalid technician IRI'], 400);
        }
        
        // Vérification de la charge actuelle du technicien
        $currentLoad = $technician->getOpenTicketsCount();
        $newLoad = $currentLoad + 1;

        // Blocage si le technicien a déjà 3 tickets ou plus
        if ($currentLoad >= 3) {
            return new JsonResponse([
                'error' => 'Technician is overloaded',
                'currentStatus' => $technician->getTechStatus()->value,
                'currentTickets' => $currentLoad,
                'maxCapacity' => 3
            ], 400);
        }

        // Désassignation de l'ancien technicien si existe
        if ($oldTechnician = $ticket->getAssignedTo()) {
            $oldTechnician->updateStatus($oldTechnician->getOpenTicketsCount() - 1);
        }

        // Assignation du nouveau technicien
        $ticket->setAssignedTo($technician)
            ->setStatus(TicketStatus::ASSIGNED);

        // Mise à jour du statut du technicien
        $technician->setTechStatus(match(true) {
            $newLoad >= 3 => TechnicianStatus::BUSY,
            $newLoad == 2 => TechnicianStatus::ACTIVE,
            default => TechnicianStatus::AVAILABLE
        });


          // Envoyer une notification
          $this->notificationService->createNotification(
            $technician,
            sprintf('Le ticket #%d vous a été assigné manuellement', $ticket->getId()),
            '/notifications/' . $technician->getId(),
            'ticket_assigned'
        );

        $this->notificationService->publishTicketUpdate($ticket);

        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $ticket->getId(),
            'assignedTo' => $technicianIri,
            'technician' => [
                'id' => $technician->getId(),
                'status' => $technician->getTechStatus()->value,
                'currentTickets' => $newLoad
            ],
            'message' => match($technician->getTechStatus()) {
                TechnicianStatus::BUSY => 'Warning: Technician is now busy (3 tickets)',
                TechnicianStatus::ACTIVE => 'Technician is active (2 tickets)',
                TechnicianStatus::AVAILABLE => 'Technician is available'
            }
        ]);
    }

}