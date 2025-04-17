<?php

namespace App\Controller;

use App\Entity\Ticket;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
class StatusController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer
    ) {}

    public function __invoke(Ticket $ticket, Request $request): Ticket
    {
        $this->serializer->deserialize(
            $request->getContent(),
            Ticket::class,
            'json',
            ['object_to_populate' => $ticket, 'groups' => ['ticket:status:update']]
        );

        $this->entityManager->flush();
        return $ticket;
    }
}