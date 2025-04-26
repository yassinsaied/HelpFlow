<?php

namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;
use App\Entity\Enum\TicketStatus;
use App\Traits\TimestampableTrait;
use App\Entity\Enum\TicketPriority;
use App\Repository\TicketRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Controller\AssignmentController; 
use App\Controller\StatusController; 


#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('TICKET_VIEW', object)"),
        new Post(security: "is_granted('ROLE_CLIENT')"),
        new Put(security: "is_granted('TICKET_EDIT', object)"),
        new Patch(
            security: "is_granted('TICKET_EDIT', object)",
            inputFormats: ['json' => ['application/merge-patch+json']],
            denormalizationContext: ['groups' => ['ticket:update']]
        ),
        new Patch(
            uriTemplate: '/tickets/{id}/assign',
            security: "is_granted('TICKET_ASSIGN', object)", // Correspond à la constante du voter
            denormalizationContext: ['groups' => ['ticket:assignment']],
            controller: AssignmentController::class
        ),
        new Patch(
            uriTemplate: '/tickets/{id}/status',
            security: "is_granted('TICKET_CHANGE_STATUS', object)", // Correspond à la constante du voter
            denormalizationContext: ['groups' => ['ticket:status:update' , 'ticket:status:update']],
            controller: StatusController::class
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['ticket:read']],
    denormalizationContext: ['groups' => ['ticket:write']]
)]

#[ORM\HasLifecycleCallbacks] 
class Ticket
{

    use TimestampableTrait;


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ticket:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['ticket:read', 'ticket:write', 'ticket:update'])]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['ticket:read', 'ticket:write', 'ticket:update'])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'createdTickets')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ticket:read'])]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(inversedBy: 'assignedTickets')]
    #[Groups([
        'ticket:read', 
        'ticket:assignment', // Pour l'assignation manuelle
        'ticket:update' // Permet de modifier l'assignation via PATCH standard
    ])]
    private ?User $assignedTo = null;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ticket:read'])]
    private ?Organization $organization = null;


    #[ORM\Column(type: 'string', enumType: TicketPriority::class)]
    #[Assert\NotBlank]
    #[Groups([
        'ticket:read', 
        'ticket:write', // Défini à la création
        'ticket:update' // Modifiable après création
    ])]
    private ?TicketPriority $priority = null;


    #[ORM\Column(type: 'string', enumType: TicketStatus::class)]
    #[Groups([
        'ticket:read',
        'ticket:status:update', // Modification via le contrôleur dédié
        'ticket:update' // Modification via PATCH standard
    ])]
    private TicketStatus $status = TicketStatus::NEW;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): static
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;

        return $this;
    }

    public function getPriority(): ?TicketPriority
    {
        return $this->priority;
    }

    public function setPriority(TicketPriority $priority): static
    {
        $this->priority = $priority;

        return $this;
    }


    public function getStatus(): ?TicketStatus
    {
        return $this->status;
    }

    public function setStatus(TicketStatus $status): static
    {
        $this->status = $status;

        return $this;
    }
}
