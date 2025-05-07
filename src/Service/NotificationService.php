<?php
namespace App\Service;

use App\Entity\User;
use App\Entity\Ticket;
use DateTimeImmutable;
use App\Entity\Notification;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\Update;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HubInterface $hub,
        private LoggerInterface $logger
    ) {}

    public function createNotification(
        User $recipient,
        string $message,
        ?string $topic = null,
        ?string $type = null ,
        
        

    ): Notification {
        $notification = new Notification();
        $notification->setRecipient($recipient)
            ->setMessage($message)
            ->setTopic($topic)
            ->setType($type)
            ->setIsRead(false);
        

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        // Publier sur Mercure
        $this->publishMercureUpdate($notification);

        return $notification;
    }


    public function publishTicketUpdate(Ticket $ticket): bool
    {
        try {
            $update = new Update(
                ['/tickets/' . $ticket->getId(), '/user/' . $ticket->getAssignedTo()?->getId()],
                json_encode([
                    '@id' => '/api/tickets/' . $ticket->getId(),
                    '@type' => 'Ticket',
                    'id' => $ticket->getId(),
                    'status' => $ticket->getStatus()->value,
                    'assignedTo' => $ticket->getAssignedTo()?->getId(),
                    'updatedAt' => $ticket->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
                    'event' => 'ticket.updated'
                ]),
                true // Private update
            );

            $this->hub->publish($update);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to publish ticket update to Mercure', [
                'exception' => $e->getMessage(),
                'ticket_id' => $ticket->getId()
            ]);
            return false;
        }
    }

    private function publishMercureUpdate(Notification $notification): bool
    {
        try {
            $update = new Update(
                $notification->getTopic() ?? '/notifications/' . $notification->getRecipient()->getId(),
                json_encode([
                    '@id' => '/api/notifications/' . $notification->getId(),
                    '@type' => 'Notification',
                    'id' => $notification->getId(),
                    'message' => $notification->getMessage(),
                    'isRead' => $notification->isRead(),
                    'createdAt' => $notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
                    'type' => $notification->getType(),
                ]),
                // $notification->getTopic() !== null // Private si topic spécifique
            );

            // dd( $this->hub->publish($update)) ;
         
            $this->hub->publish($update);
           
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to publish notification to Mercure', [
                'exception' => $e->getMessage(),
                'notification_id' => $notification->getId()
            ]);
            return false;
        }
    }

    // private function publishMercureUpdate(Notification $notification): void
    // {
    //     $update = new Update(
    //         $notification->getTopic() ?? '/notifications/' . $notification->getRecipient()->getId(),
    //         json_encode([
    //             'id' => $notification->getId(),
    //             'message' => $notification->getMessage(),
    //             'isRead' => $notification->isRead(),
    //             'createdAt' => $notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
    //             'type' => $notification->getType(),
    //         ])
    //     );

    //     $this->hub->publish($update);
    // }

    public function markAsRead(Notification $notification): void
    {
        $notification->setIsRead(true);
        $this->entityManager->flush();

        // Mettre à jour via Mercure
        $this->publishMercureUpdate($notification);
    }
}