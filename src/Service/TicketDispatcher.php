<?php
// src/Service/TicketDispatcher.php

namespace App\Service;

use App\Entity\User;
use App\Entity\Ticket;
use App\Entity\Enum\TicketStatus;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class TicketDispatcher
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function assignTicket(int $ticketId, int $organizationId): void
    {
        $ticket = $this->entityManager->find(Ticket::class, $ticketId);
        
        if (!$ticket) {
            throw new \InvalidArgumentException('Ticket not found');
        }

        $technician = $this->findBestTechnician($organizationId);

        if ($technician) {
            $ticket->setAssignedTo($technician)
                ->setStatus(TicketStatus::ASSIGNED);

            $this->entityManager->flush();
        }
    }

  // src/Service/TicketDispatcher.php

    public function findBestTechnician(int $organizationId): ?User
    {
        $technicians = $this->userRepository
            ->findAvailableTechnicians($organizationId);

        if (empty($technicians)) {
            return null;
        }

        // Tri par statut prioritaire
        usort($technicians, function(User $a, User $b) {
            return $a->getTechStatus() <=> $b->getTechStatus();
        });

        $selected = $technicians[0];
        $selected->updateStatus($selected->getOpenTicketsCount() + 1);
        
        return $selected;
    }
}