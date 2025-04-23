<?php 
// src/MessageHandler/TicketAssignmentHandler.php

namespace App\MessageHandler;

use App\Entity\Ticket;
use App\Entity\Enum\TicketStatus;
use App\Service\TicketDispatcher;
use App\Message\TicketAssignmentMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class TicketAssignmentHandler
{
    public function __construct(
        private TicketDispatcher $ticketDispatcher,
        private EntityManagerInterface $em
    ) {}

    public function __invoke(TicketAssignmentMessage $message): void
    {
        $ticket = $this->em->find(Ticket::class, $message->getTicketId());
        $technician = $this->ticketDispatcher->findBestTechnician($message->getOrganizationId());
        dd($technician  ,  $ticket ) ;
        if ($technician) {
            $ticket->setAssignedTo($technician)
                   ->setStatus(TicketStatus::ASSIGNED);
    
            $this->em->flush();
            
            // Mise à jour du statut
            $technician->updateStatus($technician->getOpenTicketsCount());
            $this->em->flush();
        }
        
        
        // $this->ticketDispatcher->assignTicket(
        //     $message->getTicketId(),
        //     $message->getOrganizationId()
        // );
    }
}