<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Ticket;
use App\Entity\Enum\TicketStatus;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\Api\IriConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsController]
class AssignmentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private IriConverterInterface $iriConverter
    ) {}

    public function __invoke(Ticket $ticket, Request $request): Ticket
    {
        $data = json_decode($request->getContent(), true);
        $assignedToIri = $data['assignedTo'] ?? null;

        if (!$assignedToIri) {
            throw new \InvalidArgumentException('assignedTo is required');
        }

        $user = $this->iriConverter->getResourceFromIri($assignedToIri);
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('Invalid user IRI');
        }

        $ticket->setAssignedTo($user);

        if ($ticket->getStatus() === TicketStatus::NEW) {
            $ticket->setStatus(TicketStatus::ASSIGNED);
        }

        $this->entityManager->flush();

        return $ticket;
    }

}