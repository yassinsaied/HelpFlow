<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

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



    public function findAvailableTechnicians(int $organizationId): array
    {
        return $this->createQueryBuilder('u')
        ->select('u.id, u.email, COUNT(t.id) AS assignedTicketsCount')
        ->leftJoin('u.assignedTickets', 't') // Jointure sur les tickets assignés
        ->where('u.organization = :orgId')
        ->andWhere('u.isActive = true')
        ->andWhere('u.roles LIKE :roles') // Filtrage des techniciens
        ->groupBy('u.id') // Regroupement par utilisateur
        ->having('COUNT(t.id) < 3') // Filtrage des utilisateurs avec moins de 3 tickets
        ->orderBy('COUNT(t.id)', 'ASC') // Priorité aux moins chargés
        ->setParameter('orgId', $organizationId)
        ->setParameter('roles', '%"ROLE_TECHNICIAN"%') // Format JSON pour les rôles
        ->getQuery()
        ->getResult();
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
