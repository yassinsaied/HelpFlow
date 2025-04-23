<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Enum\TicketStatus;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }



    public function findAvailableTechnicians(): array
    {  
        $results = $this->createQueryBuilder('u')
        ->select('u, COUNT(t.id) as ticketCount')
        ->leftJoin('u.assignedTickets', 't', 'WITH', 't.status != :resolvedStatus')
        ->where('u.isActive = true')
        ->andWhere('u.roles LIKE :role')
        ->andWhere('u.organization IS NULL') // Techniciens sans organisation
        ->groupBy('u.id')
        ->having('ticketCount < 3') // Moins de 3 tickets actifs
        ->orderBy('ticketCount', 'ASC') // Priorité aux moins chargés
        ->setParameter('role', '%ROLE_TECHNICIAN%')
        ->setParameter('resolvedStatus', TicketStatus::RESOLVED->value)
        ->getQuery()
        ->getResult();

        // Retourne uniquement les entités User
      return array_map(fn($item) => $item[0], $results);

    }



//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
