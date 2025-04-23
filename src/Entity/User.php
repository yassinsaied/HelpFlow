<?php

namespace App\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use App\Entity\Enum\TechnicianStatus;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Enum\TicketStatus;
use App\Repository\UserRepository;
use App\Traits\TimestampableTrait;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use App\State\Processor\UserPasswordHasherProcessor;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email' , groups: ['user:create']) ,]
#[ApiResource(
    operations: [
        new Get(
            security: "is_granted('ROLE_ADMIN') or object == user",
            normalizationContext: ['groups' => ['user:read', 'user:profile' , 'user:item:get']]
        ),
        new GetCollection(
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['user:read']]
        ),
        new Post(
            validationContext: ['groups' => ['user:create']],
            security: "is_granted('PUBLIC_ACCESS')", // Inscription publique
            processor: UserPasswordHasherProcessor::class,
            denormalizationContext: ['groups' => ['user:create']]
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN') or object == user",
            processor: UserPasswordHasherProcessor::class,
            denormalizationContext: ['groups' => ['user:profile:write', 'user:admin:write']]
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN') or object == user",
            processor: UserPasswordHasherProcessor::class,
            // inputFormats: ['jsonmergepatch' => ['application/merge-patch+json']],
            denormalizationContext: [
                'groups' => ['user:profile:write', 'user:admin:write']
            ]),
        
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ],
    normalizationContext: ['groups' => ['user:read']],
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    
    use TimestampableTrait;

    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'organization:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\Email]
    #[Assert\NotBlank(groups: ['user:create'])]
    #[Groups(['user:read', 'user:profile:write', 'user:create'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Assert\NotBlank]
    #[Groups(['user:read', 'user:admin:write'])] // Admin seulement
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;


    #[Assert\NotBlank(groups: ['user:create'])]
    #[Assert\Length(min: 6, max: 50)]
    #[Groups(['user:create', 'user:profile:write'])]
    private ?string $plainPassword = null;

    /**
     * @var Collection<int, Alert>
     */
    #[ORM\OneToMany(targetEntity: Alert::class, mappedBy: 'user')]
    private Collection $alerts;

    #[ORM\Column(length: 255 , nullable:true)]
    #[Groups(['user:read', 'user:profile:write', 'user:admin:write'])]

    private ?string $firstName = null;

    #[ORM\Column(length: 255 , nullable:true)]
    #[Groups(['user:read', 'user:profile:write', 'user:admin:write'])]
    private ?string $lastName = null;

    #[ORM\Column]
    #[Groups(['user:read', 'user:admin:write' , 'user:create'])] // Admin seulement
    private ?bool $isActive = false;
 

    #[ORM\ManyToOne(inversedBy: 'employers')]
    #[Groups(['user:read'])]
    #[MaxDepth(1)] 
    private ?Organization $organization = null;

    /**
     * @var Collection<int, Ticket>
     */
    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'createdBy', orphanRemoval: true)]
    #[Groups(['user:item:get'])]
    private Collection $createdTickets;

    /**
     * @var Collection<int, Ticket>
     */
    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'assignedTo')]
    #[Groups(['user:item:get'])]
    private Collection $assignedTickets;

    #[ORM\Column(type: 'string', enumType: TechnicianStatus::class)]
    #[Groups(['user:read'])]
    private ?TechnicianStatus $TechStatus = TechnicianStatus::AVAILABLE;

    public function __construct()
    {
        $this->alerts = new ArrayCollection();
        $this->roles = ['ROLE_CLIENT']; 
        $this->createdTickets = new ArrayCollection();
        $this->assignedTickets = new ArrayCollection(); 
    }
    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_CLIENT';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
         $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Alert>
     */
    public function getAlerts(): Collection
    {
        return $this->alerts;
    }

    public function addAlert(Alert $alert): static
    {
        if (!$this->alerts->contains($alert)) {
            $this->alerts->add($alert);
            $alert->setUser($this);
        }

        return $this;
    }

    public function removeAlert(Alert $alert): static
    {
        if ($this->alerts->removeElement($alert)) {
            // set the owning side to null (unless already changed)
            if ($alert->getUser() === $this) {
                $alert->setUser(null);
            }
        }

        return $this;
    }

    public function getPlainPassword(): ?string 
    {

         return $this->plainPassword; 
    }
    public function setPlainPassword(string $plainPassword): void 
    {
         $this->plainPassword = $plainPassword; 
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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

  
    /**
     * @return Collection<int, Ticket>
     */
    public function getCreatedTickets(): Collection
    {
        return $this->createdTickets;
    }

    public function addCreatedTicket(Ticket $createdTicket): static
    {
        if (!$this->createdTickets->contains($createdTicket)) {
            $this->createdTickets->add($createdTicket);
            $createdTicket->setCreatedBy($this);
        }

        return $this;
    }

    public function removeCreatedTicket(Ticket $createdTicket): static
    {
        if ($this->createdTickets->removeElement($createdTicket)) {
            // set the owning side to null (unless already changed)
            if ($createdTicket->getCreatedBy() === $this) {
                $createdTicket->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getAssignedTickets(): Collection
    {
        return $this->assignedTickets;
    }

    public function addAssignedTicket(Ticket $assignedTicket): static
    {
        if (!$this->assignedTickets->contains($assignedTicket)) {
            $this->assignedTickets->add($assignedTicket);
            $assignedTicket->setAssignedTo($this);
        }

        return $this;
    }

    public function removeAssignedTicket(Ticket $assignedTicket): static
    {
        if ($this->assignedTickets->removeElement($assignedTicket)) {
            // set the owning side to null (unless already changed)
            if ($assignedTicket->getAssignedTo() === $this) {
                $assignedTicket->setAssignedTo(null);
            }
        }

        return $this;
    }


    public function getOpenTicketsCount(): int
    {
        return $this->assignedTickets
            ->filter(fn(Ticket $t) => !in_array($t->getStatus(), [
                TicketStatus::RESOLVED
            ]))
            ->count();
    }

    public function hasRole(string $role): bool
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    public function getTechStatus(): ?TechnicianStatus
    {
        return $this->TechStatus;
    }

    public function setTechStatus(TechnicianStatus $TechStatus): static
    {
        $this->TechStatus = $TechStatus;

        return $this;
    }

    public function updateStatus(int $openTicketsCount): void
    {
        $this->status = match(true) {
            $openTicketsCount <= 1 => TechnicianStatus::AVAILABLE,
            $openTicketsCount === 2 => TechnicianStatus::ACTIVE,
            default => TechnicianStatus::BUSY
        };
    }

    
}
