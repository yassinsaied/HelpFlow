<?php

namespace App\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use App\Traits\TimestampableTrait;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\OrganizationRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['organization:read']]
        ),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['organization:write' ,'organization:admin:write']]
        ),
        new Get(
            normalizationContext: ['groups' => ['organization:read', 'organization:item:get']]
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['organization:write' , 'organization:admin:write']]
        ),
        new Patch( 
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['organization:write', 'organization:admin:write']]
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        )
    ],
)]
class Organization
{
    use TimestampableTrait;


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['organization:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 255)]
    #[Groups(['organization:write' ,'organization:admin:write'])]
    private ?string $address = null;

 

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'organization')]
    #[Groups(['organization:read','organization:item:get'])]
    #[MaxDepth(1)]
    private Collection $employers;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['organization:write' ,'organization:admin:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    #[Groups(['organization:read', 'organization:write'])]
    private ?string $name = null;

    /**
     * @var Collection<int, Ticket>
     */
    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'organization')]
    #[Groups(['organization:item:get'])]
    private Collection $tickets;

    public function __construct()
    {
        $this->employers = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->tickets = new ArrayCollection(); 
   
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }


    /**
     * @return Collection<int, User>
     */
    public function getEmployers(): Collection
    {
        return $this->employers;
    }

    public function addEmployer(User $employer): static
    {
        if (!$this->employers->contains($employer)) {
            $this->employers->add($employer);
            $employer->setOrganization($this);
        }

        return $this;
    }

    public function removeEmployer(User $employer): static
    {
        if ($this->employers->removeElement($employer)) {
            // set the owning side to null (unless already changed)
            if ($employer->getOrganization() === $this) {
                $employer->setOrganization(null);
            }
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setOrganization($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getOrganization() === $this) {
                $ticket->setOrganization(null);
            }
        }

        return $this;
    }
}
