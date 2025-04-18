<?php

// namespace App\EventSubscriber;

// use App\Entity\Ticket;
// use App\Message\TicketAssignmentMessage;
// use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
// use Doctrine\ORM\Event\PostPersistEventArgs;
// use Doctrine\ORM\Events;
// use Symfony\Component\Messenger\MessageBusInterface;

// class TicketCreationSubscriber implements EventSubscriberInterface
// {
//     public function __construct(
//         private MessageBusInterface $messageBus
//     ) {}

//     public function getSubscribedEvents(): array
//     {
//         return [
//             Events::postPersist,
//         ];
//     }

//     public function postPersist(PostPersistEventArgs $args): void
//     {
//         $entity = $args->getObject();

//         if ($entity instanceof Ticket) {
//             $this->messageBus->dispatch(
//                 new TicketAssignmentMessage(
//                     $entity->getId(),
//                     $entity->getOrganization()->getId()
//                 )
//             );
//         }
//     }

// }
