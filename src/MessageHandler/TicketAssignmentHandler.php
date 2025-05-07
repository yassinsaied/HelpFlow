<?php 
// src/MessageHandler/TicketAssignmentHandler.php

namespace App\MessageHandler;

use App\Entity\Ticket;
use App\Service\TicketDispatcher;
use App\Message\TicketAssignmentMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class TicketAssignmentHandler
{
    public function __construct(
        private TicketDispatcher $ticketDispatcher,
        private EntityManagerInterface $entityManager,
       
    ) {}

    public function __invoke(TicketAssignmentMessage $message): void
    {
        $ticket = $this->entityManager->find(Ticket::class, $message->getTicketId());
        
        if ($ticket) {
            $this->ticketDispatcher->assignTicket($ticket);
        }

      
    }
}