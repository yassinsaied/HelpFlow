<?php
namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    #[Route('/', name: 'notifications_list', methods: ['GET'])]
    public function index(NotificationRepository $repository, Security $security): JsonResponse
    {
        $user = $security->getUser();
        $notifications = $repository->findBy(
            ['recipient' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->json($notifications);
    }

    #[Route('/{id}/read', name: 'notification_mark_as_read', methods: ['POST'])]
    public function markAsRead(
        Notification $notification,
        EntityManagerInterface $entityManager,
        Security $security
    ): JsonResponse {
        $user = $security->getUser();

        if ($notification->getRecipient() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $notification->setIsRead(true);
        $entityManager->flush();

        return $this->json($notification);
    }

    #[Route('/unread-count', name: 'notifications_unread_count', methods: ['GET'])]
    public function unreadCount(NotificationRepository $repository, Security $security): JsonResponse
    {
        $user = $security->getUser();
        $count = $repository->count([
            'recipient' => $user,
            'isRead' => false
        ]);

        return $this->json(['count' => $count]);
    }
}