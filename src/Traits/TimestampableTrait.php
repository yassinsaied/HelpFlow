<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


trait TimestampableTrait
{
    #[ORM\Column]
    #[Groups([
        'user:read', 'organization:read', 'ticket:read', 'notification:read',
        'user:write', 'organization:write', 'ticket:write', 'notification:write'
    ])]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups([
        'user:read', 'organization:read', 'ticket:read', 'notification:read',
        'user:write', 'organization:write', 'ticket:write', 'notification:write'
    ])]    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAt(): void
    {

    
            $this->createdAt = new \DateTimeImmutable();
       
 
    }

    #[ORM\PreUpdate]
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

      // Ajoutez cette méthode pour permettre le setting manuel
      public function manuallySetCreatedAt(\DateTimeImmutable $date): static
      {
          $this->createdAt = $date;
          return $this;
      }
}