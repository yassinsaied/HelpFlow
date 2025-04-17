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
    public const TICKET_CHANGE_STATUS = 'CHANGE_STATUS';

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
        return $user->hasRole('ROLE_ADMIN') 
            || ($user->hasRole('ROLE_MANAGER') && $this->sameOrganization($ticket, $user))
            || $user === $ticket->getCreatedBy()
            || $user === $ticket->getAssignedTo();
    }

    private function canEdit(Ticket $ticket, User $user): bool
    {
        return $user->hasRole('ROLE_ADMIN')
            || ($user->hasRole('ROLE_MANAGER') && $this->sameOrganization($ticket, $user))
            || $user === $ticket->getAssignedTo();
    }

    private function canAssign(Ticket $ticket, User $user): bool
    {
        return $user->hasRole('ROLE_ADMIN')
            || ($user->hasRole('ROLE_MANAGER') && $this->sameOrganization($ticket, $user));
    }

    private function canChangeStatus(Ticket $ticket, User $user): bool
    {
        return $user->hasRole('ROLE_ADMIN')
            || ($user->hasRole('ROLE_MANAGER') && $this->sameOrganization($ticket, $user))
            || $user === $ticket->getCreatedBy()
            || $user === $ticket->getAssignedTo();
    }

    private function sameOrganization(Ticket $ticket, User $user): bool
    {
        return $user->getOrganization() === $ticket->getOrganization();
    }
}