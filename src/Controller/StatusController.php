<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Ticket;
use App\Entity\Enum\TicketStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsController]
class StatusController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer
    ) {}

    public function __invoke(Ticket $ticket, Request $request): JsonResponse
    {   

        $this->entityManager->refresh($ticket); // Recharge depuis la base
     
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $newStatusValue = $data['status'] ?? null;

        // Validation du statut
        if (!$newStatusValue || !$status = TicketStatus::tryFrom($newStatusValue)) {
            return new JsonResponse([
                'error' => 'Invalid status value',
                'allowed_statuses' => array_map(fn($case) => $case->value, TicketStatus::cases())
            ], 400);
        }

        // Restrictions spécifiques pour les techniciens
        if ($user->hasRole('ROLE_TECHNICIAN')) {
            $this->validateTechnicianStatusChange($ticket, $user, $status);
        }

         // Historique avant modification
         $oldStatus = $ticket->getStatus();
         $oldTechnicianStatus = $ticket->getAssignedTo()?->getTechStatus();
 
         // Modification du statut
         $ticket->setStatus($status);

         $this->entityManager->flush();

         return new JsonResponse([
             'id' => $ticket->getId(),
             'old_status' => $oldStatus->value,
             'new_status' => $ticket->getStatus()->value,
             'assigned_to' => $ticket->getAssignedTo()?->getId(),
             'technician_status' => $ticket->getAssignedTo()?->getTechStatus()?->value,
             'message' => $this->getStatusChangeMessage($oldStatus, $ticket->getStatus())
         ]);
    }

    private function validateTechnicianStatusChange(Ticket $ticket, User $user, TicketStatus $newStatus): void
    {
        // Vérifie que le technicien est bien assigné à ce ticket
        if ($user !== $ticket->getAssignedTo()) {
            throw new AccessDeniedException('You can only change status of tickets assigned to you');
        }

        // Transitions autorisées pour les techniciens
        $allowedTransitions = [
            TicketStatus::ASSIGNED->value => [TicketStatus::IN_PROGRESS->value],
            TicketStatus::IN_PROGRESS->value => [TicketStatus::RESOLVED->value]
        ];

        $currentStatus = $ticket->getStatus()->value;
  
        $allowed = $allowedTransitions[$currentStatus] ?? [];
     
        if (!in_array($newStatus->value, $allowed)) {
            throw new AccessDeniedException(sprintf(
                'Technicians can only change status from %s to %s',
                $currentStatus,
                implode(' or ', $allowed)
            ));
        }
    }

    private function getStatusChangeMessage(TicketStatus $oldStatus, TicketStatus $newStatus): string
    {
        $messages = [
            TicketStatus::NEW->value => [
                TicketStatus::ASSIGNED->value => 'Ticket has been assigned'
            ],
            TicketStatus::ASSIGNED->value => [
                TicketStatus::IN_PROGRESS->value => 'Work has started on the ticket'
            ],
            TicketStatus::IN_PROGRESS->value => [
                TicketStatus::RESOLVED->value => 'Ticket has been resolved'
            ]
        ];

        return $messages[$oldStatus->value][$newStatus->value] 
            ?? sprintf('Status changed from %s to %s', $oldStatus->value, $newStatus->value);
    }

}