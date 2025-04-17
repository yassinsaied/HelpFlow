<?php 

namespace App\State\Processor;

use App\Entity\User;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\SecurityBundle\Security;

class UserPasswordHasherProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $id = $uriVariables['id'] ?? null;


        //  CREATE (POST)
        if ($id === null) {
            $this->handleNewUser($data);
            $this->entityManager->persist($data);
        }

        // UPDATE (PUT/PATCH)
        else {
           
            $existingUser = $this->entityManager->getRepository(User::class)->find($id);
           
            if (!$existingUser) {
                throw new NotFoundHttpException('User not found');
            }
            $this->handleExistingUser($data, $existingUser);
            $data = $existingUser; // On retourne l'entité gérée par Doctrine
           
        }

        // [3] Gestion commune du mot de passe
        if ($data->getPlainPassword()) {
            $this->updatePassword($data);
        }

        $this->entityManager->flush();

        return $data;
    }

    private function handleNewUser(User $user): void
    {
        // Initialisations spécifiques à la création
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setIsActive(true);
    }

    private function handleExistingUser(User $inputData, User $existingUser): void
    {
        // Copie sélective des champs modifiables
        $inputData->getEmail() && $existingUser->setEmail($inputData->getEmail()) ;
        $existingUser->setFirstName($inputData->getFirstName());
        $existingUser->setLastName($inputData->getLastName());
        $existingUser->setIsActive($inputData->isActive());
        
        // Seul l'admin peut modifier ces champs
        if ($this->isAdminRequest()) {
            $existingUser->setRoles($inputData->getRoles());
            $existingUser->setOrganization($inputData->getOrganization());
        }
    }

    private function updatePassword(User $user): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $user->getPlainPassword()
        );
        $user->setPassword($hashedPassword);
        $user->eraseCredentials();
    }

    private function isAdminRequest(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }
}