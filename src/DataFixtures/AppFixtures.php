<?php 

namespace App\DataFixtures;

use App\Entity\Enum\TicketStatus;
use App\Entity\Enum\TicketPriority;
use App\Entity\Organization;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // 1. Création des organisations
        $organizations = [];
        $orgNames = ['InnovaTech', 'DevSolutions', 'CloudCorp'];

        foreach ($orgNames as $name) {
            $organization = new Organization();
            $organization->setName($name)
                ->setAddress("Adresse de $name")
                ->setDescription("Description de $name");
            
            $manager->persist($organization);
            $organizations[] = $organization;
        }

        // 2. Création des utilisateurs globaux (Admin + Techniciens)
        $globalUsers = [];

        // Admin global
        $admin = $this->createUser(
            'admin@support.com',
            'admin123',
            ['ROLE_ADMIN'],
            null,
          
        );
        $manager->persist($admin);

        // Techniciens globaux (5)
        for ($i = 1; $i <= 5; $i++) {
            $tech = $this->createUser(
                "tech{$i}@support.com",
                'password',
                ['ROLE_TECHNICIAN'],
                null,
              ) ;
            
            $manager->persist($tech);
            $globalUsers[] = $tech;
        }

        // Compteur de tickets par technicien
        $techTicketCounts = array_combine(
            array_map(fn($tech) => $tech->getEmail(), $globalUsers),
            array_fill(0, count($globalUsers), 0)
        );

        // 3. Création des utilisateurs par organisation
        foreach ($organizations as $org) {
            // Manager
            $managerUser = $this->createUser(
                "manager@{$org->getName()}.com",
                'password',
                ['ROLE_MANAGER'],
                $org,
                
            );
            $manager->persist($managerUser);

            // Clients (5 par organisation)
            for ($i = 1; $i <= 5; $i++) {
                $client = $this->createUser(
                    "client{$i}@{$org->getName()}.com",
                    'password',
                    ['ROLE_CLIENT'],
                    $org,
                    
                );
                $manager->persist($client);

                // Création des tickets avec suivi du compteur
                $this->createTicketsForClient(
                    $client, 
                    $globalUsers,
                    $manager,
                    $techTicketCounts
                );
            }
        }

        $manager->flush();
    }

    private function createUser(
        string $email,
        string $password,
        array $roles,
        ?Organization $organization
    ): User {
        $user = new User();
        $user->setEmail($email)
            ->setPassword($this->passwordHasher->hashPassword($user, $password))
            ->setRoles($roles)
            ->setIsActive(true)
            ->setOrganization($organization)
            ->setFirstName(explode('@', $email)[0])
            ->setLastName('Doe')
            ->setCreatedAt();
    
        return $user;
    }
    

    private function createTicketsForClient(
        User $client, 
        array $technicians,
        ObjectManager $manager,
        array &$techTicketCounts
    ): void {
        $statuses = TicketStatus::cases();
        $priorities = TicketPriority::cases();

        for ($i = 0; $i < rand(1, 10); $i++) {
            $ticket = new Ticket();
            $ticket->setTitle("Problème " . ($i + 1))
                ->setDescription("Description détaillée du problème n°" . ($i + 1))
                ->setCreatedBy($client)
                ->setOrganization($client->getOrganization())
                ->setPriority($priorities[array_rand($priorities)])
                ->setStatus($statuses[array_rand($statuses)])
                ->manuallySetCreatedAt(new \DateTimeImmutable());

            // Assignation uniquement si le statut n'est pas NOUVEAU
            if ($ticket->getStatus() !== TicketStatus::NEW) {
                // Filtre les techniciens avec moins de 3 tickets ET rôle valide
                $availableTechs = array_filter(
                    $technicians,
                    function($tech) use ($techTicketCounts) {
                        return 
                            in_array('ROLE_TECHNICIAN', $tech->getRoles()) &&
                            $techTicketCounts[$tech->getEmail()] < 3;
                    }
                );
    
                if (!empty($availableTechs)) {
                    $selectedTech = $availableTechs[array_rand($availableTechs)];
                    
                    // Assignation du ticket
                    $ticket->setAssignedTo($selectedTech);
                    
                    // Mise à jour du compteur
                    $techTicketCounts[$selectedTech->getEmail()]++;
                    
                    // MAJ statut BASÉE SUR LE RÔLE
                    if (in_array('ROLE_TECHNICIAN', $selectedTech->getRoles())) {
                        $newStatus = min($techTicketCounts[$selectedTech->getEmail()], 3);
                        $selectedTech->updateStatus($newStatus);
                        $manager->persist($selectedTech); // Sauvegarde du statut
                    }
                }
            }

            $manager->persist($ticket);
        }
    }
}