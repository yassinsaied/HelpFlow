<?php

namespace App\EventSubscriber;

use App\Entity\Ticket;
use App\Message\TicketAssignmentMessage;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Symfony\Component\Security\Core\Security;
use App\Entity\User;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::postPersist)]
class TicketCreationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private Security $security
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::postPersist
        ];
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Ticket && !$entity->getCreatedBy()) {
            /** @var User $user */
            $user = $this->security->getUser();
            
            if ($user) {
                $entity->setCreatedBy($user)
                       ->setOrganization($user->getOrganization());
            }
        }
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Ticket) {
            $this->messageBus->dispatch(
                new TicketAssignmentMessage(
                    $entity->getId(),
                    $entity->getOrganization()->getId()
                )
            );
        }
    }

}
