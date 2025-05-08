<?php
// src/Service/TicketDispatcher.php

namespace App\Service;

use App\Entity\User;
use App\Entity\Ticket;
use App\Entity\Enum\TicketStatus;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use App\Entity\Enum\TechnicianStatus;
use Doctrine\ORM\EntityManagerInterface;

class TicketDispatcher
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService
    ) {}

    public function assignTicket(Ticket $ticket): void
    {
        $technician = $this->findBestTechnician();
        
        if ($technician) {
            $this->assignToTechnician($ticket, $technician);
        }

        // Si aucun technicien, le ticket reste en NEW

       
    }

    private function findBestTechnician(): ?User
    {
        $technicians = $this->userRepository->findAvailableTechnicians();
        return $technicians[0] ?? null;
    }

    
    private function assignToTechnician(Ticket $ticket, User $technician): void
    {
        // 1. Assigner le ticket
        $ticket->setAssignedTo($technician)
               ->setStatus(TicketStatus::ASSIGNED);

        // 2. Mettre à jour le statut du technicien
        $this->updateTechnicianStatus($technician);

        // 3. Envoyer une notification
        $this->notificationService->createNotification(
            $technician,
              sprintf('Un nouveau ticket #%d vous a été assigné', $ticket->getId()),
                sprintf(NotificationService::TOPIC_TECHNICIAN, $technician->getId()),
                 'ticket_assigned'
        );

        $this->entityManager->flush();
    }

    private function updateTechnicianStatus(User $technician): void
    {
        $openTicketsCount = $technician->getAssignedTickets()
            ->filter(fn(Ticket $t) => $t->getStatus() !== TicketStatus::RESOLVED)
            ->count();

        $newStatus = match(true) {
            $openTicketsCount >= 3 => TechnicianStatus::BUSY,
            $openTicketsCount === 2 => TechnicianStatus::ACTIVE,
            default => TechnicianStatus::AVAILABLE
        };

        $technician->setTechStatus($newStatus);
    }
}