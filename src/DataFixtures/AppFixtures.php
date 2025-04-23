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
            null
        );
        $manager->persist($admin);

        // Techniciens globaux (5)
        for ($i = 1; $i <= 5; $i++) {
            $tech = $this->createUser(
                "tech{$i}@support.com",
                'password',
                ['ROLE_TECHNICIAN'],
                null,
                rand(0, 3)) ;
            
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
                null
            );
            $manager->persist($managerUser);

            // Clients (5 par organisation)
            for ($i = 1; $i <= 5; $i++) {
                $client = $this->createUser(
                    "client{$i}@{$org->getName()}.com",
                    'password',
                    ['ROLE_CLIENT'],
                    $org,
                    null
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
        ?Organization $organization,
        ?int $techStatus
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

        if ($techStatus !== null) {
            $user->updateStatus($techStatus);
        }

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
                $availableTechs = array_filter(
                    $technicians,
                    fn($tech) => $techTicketCounts[$tech->getEmail()] < 3
                );

                if (!empty($availableTechs)) {
                    $selectedTech = $availableTechs[array_rand($availableTechs)];
                    $ticket->setAssignedTo($selectedTech);
                    $techTicketCounts[$selectedTech->getEmail()]++;
                }
            }

            $manager->persist($ticket);
        }
    }
}