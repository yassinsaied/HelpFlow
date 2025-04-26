<?php

namespace App\Security\Voter;

use App\Entity\Ticket;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class TicketVoter extends Voter
{
    public const TICKET_VIEW = 'TICKET_VIEW';
    public const TICKET_EDIT = 'TICKET_EDIT';
    public const TICKET_ASSIGN = 'TICKET_ASSIGN';
    public const TICKET_CHANGE_STATUS = 'TICKET_CHANGE_STATUS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Ticket 
            && in_array($attribute, [
                self::TICKET_VIEW, 
                self::TICKET_EDIT, 
                self::TICKET_ASSIGN, 
                self::TICKET_CHANGE_STATUS
            ]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        if (!$user instanceof User) {
            return false;
        }

        $ticket = $subject;

        return match ($attribute) {
            self::TICKET_VIEW => $this->canView($ticket, $user),
            self::TICKET_EDIT => $this->canEdit($ticket, $user),
            self::TICKET_ASSIGN => $this->canAssign($ticket, $user),
            self::TICKET_CHANGE_STATUS => $this->canChangeStatus($ticket, $user),
            default => false,
        };
    }

    private function canView(Ticket $ticket, User $user): bool
    {
        if ($user->hasRole('ROLE_ADMIN')) {
            return true;
        }

        // Manager voit les tickets de son organisation
        if ($user->hasRole('ROLE_MANAGER') && $this->sameOrganization($ticket, $user)) {
            return true;
        }

        // Technicien voit les tickets qui lui sont assignés
        if ($user->hasRole('ROLE_TECHNICIAN') && $user === $ticket->getAssignedTo()) {
            return true;
        }

        // Client voit les tickets qu'il a créés
        return $user === $ticket->getCreatedBy();
    }

    private function canEdit(Ticket $ticket, User $user): bool
    {
           // Client peut modifier le contenu seulement si non assigné
        if ($user === $ticket->getCreatedBy() && $ticket->getAssignedTo() === null || $user->hasRole('ROLE_TECHNICIAN') ) {
            return true;
        }

        // Admin peut tout modifier
        return $user->hasRole('ROLE_ADMIN');
    }

    private function canAssign(Ticket $ticket, User $user): bool
    {
        return $user->hasRole('ROLE_ADMIN')
            || ($user->hasRole('ROLE_MANAGER') && $this->sameOrganization($ticket, $user));
    }

    private function canChangeStatus(Ticket $ticket, User $user): bool
    {   
        // Seuls les admins et les techniciens assignés peuvent modifier le statut
        return $user->hasRole('ROLE_ADMIN')
            || ($user->hasRole('ROLE_TECHNICIAN') && $user === $ticket->getAssignedTo());
    }

    private function sameOrganization(Ticket $ticket, User $user): bool
    {
        return $user->getOrganization() === $ticket->getOrganization();
    }
}