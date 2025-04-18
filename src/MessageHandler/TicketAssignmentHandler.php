<?php 
// src/MessageHandler/TicketAssignmentHandler.php

namespace App\MessageHandler;

use App\Message\TicketAssignmentMessage;
use App\Service\TicketDispatcher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class TicketAssignmentHandler
{
    public function __construct(
        private TicketDispatcher $ticketDispatcher
    ) {}

    public function __invoke(TicketAssignmentMessage $message): void
    {
        $this->ticketDispatcher->assignTicket(
            $message->getTicketId(),
            $message->getOrganizationId()
        );
    }
}