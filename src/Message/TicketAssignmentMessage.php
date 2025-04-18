<?php


namespace App\Message;

class TicketAssignmentMessage
{
    public function __construct(
        private int $ticketId,
        private int $organizationId
    ) {}

    public function getTicketId(): int
    {
        return $this->ticketId;
    }

    public function getOrganizationId(): int
    {
        return $this->organizationId;
    }
}