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

    public const TOPIC_CLIENT = '/notifications/%d/client';
    public const TOPIC_TECHNICIAN = '/notifications/%d/technician';
    public const TOPIC_ADMIN = '/notifications/admin';


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
        $this->publishMercureUpdate($notification , $topic);

        return $notification;
    }


// Dans NotificationService.php, ajoutez cette méthode :

    public function sendResolvedNotification(Ticket $ticket): void
    {
        $client = $ticket->getCreatedBy();
     
    
        // Notification au client avec un topic spécifique
        if ($client) {
            $this->createNotification(
                $client,
                sprintf('Votre ticket #%d a été résolu', $ticket->getId()),
                sprintf(self::TOPIC_CLIENT, $client->getId()), 
                'ticket_resolved'
            );
        }
        
    }

    private function publishMercureUpdate(Notification $notification , string $topic): bool
    {
        try {

            $update = new Update(
                $topic,
                  json_encode([
                    '@id' => '/api/notifications/' . $notification->getId(),
                    '@type' => 'Notification',
                    'id' => $notification->getId(),
                    'message' => $notification->getMessage(),
                    'isRead' => $notification->isRead(),
                    'createdAt' => $notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
                    'type' => $notification->getType(),
                ]),
             
            );

              
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

  
  

    public function markAsRead(Notification $notification): void
    {
        $notification->setIsRead(true);
        $this->entityManager->flush();

    }
}