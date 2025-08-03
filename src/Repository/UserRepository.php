<?php
// src/Repository/UserRepository.php
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

    /**
     * Trouve les utilisateurs par rôle
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"'.$role.'"%')
            ->orderBy('u.lastname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les utilisateurs par rôle
     */
    public function countByRole(string $role): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"'.$role.'"%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Recherche utilisateurs par nom/prénom/email
     */
    public function searchUsers(string $query): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.firstname LIKE :query')
            ->orWhere('u.lastname LIKE :query')
            ->orWhere('u.email LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('u.lastname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les utilisateurs actifs (si vous ajoutez un champ status)
     */
    public function findActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('u.lastname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des utilisateurs par rôle
     */
    public function getUserStatsByRole(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT 
                CASE 
                    WHEN roles LIKE "%ROLE_ADMIN%" THEN "Admin"
                    WHEN roles LIKE "%ROLE_TEACHER%" THEN "Enseignant"
                    WHEN roles LIKE "%ROLE_STUDENT%" THEN "Étudiant"
                    WHEN roles LIKE "%ROLE_PARENT%" THEN "Parent"
                    ELSE "Autre"
                END as role_type,
                COUNT(*) as count
            FROM user 
            GROUP BY role_type
        ';
        
        return $conn->executeQuery($sql)->fetchAllAssociative();
    }

    /**
     * Trouve les utilisateurs créés récemment
     */
    public function findRecentUsers(int $days = 30): array
    {
        $date = new \DateTime();
        $date->sub(new \DateInterval('P'.$days.'D'));

        return $this->createQueryBuilder('u')
            ->where('u.dateCreation >= :date')
            ->setParameter('date', $date)
            ->orderBy('u.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
