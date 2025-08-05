<?php
// src/Repository/EvenementRepository.php
namespace App\Repository;

use App\Entity\Evenement;
use App\Entity\User;
use App\Entity\Classe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
     * Find events for a specific user (created by or attending)
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.creator = :user')
            ->orWhere('JSON_CONTAINS(e.attendees, :userId) = 1')
            ->setParameter('user', $user)
            ->setParameter('userId', $user->getId())
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events for a specific class
     */
    public function findByClasse(Classe $classe): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.classe = :classe')
            ->orWhere('e.isPublic = true')
            ->setParameter('classe', $classe)
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events within a date range
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate, User $user = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.date >= :startDate')
            ->andWhere('e.date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($user) {
            $qb->andWhere('e.creator = :user OR JSON_CONTAINS(e.attendees, :userId) = 1 OR e.isPublic = true')
               ->setParameter('user', $user)
               ->setParameter('userId', $user->getId());
        } else {
            $qb->andWhere('e.isPublic = true');
        }

        return $qb->orderBy('e.date', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find events by type
     */
    public function findByType(string $type, User $user = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.type = :type')
            ->setParameter('type', $type);

        if ($user) {
            $qb->andWhere('e.creator = :user OR JSON_CONTAINS(e.attendees, :userId) = 1 OR e.isPublic = true')
               ->setParameter('user', $user)
               ->setParameter('userId', $user->getId());
        } else {
            $qb->andWhere('e.isPublic = true');
        }

        return $qb->orderBy('e.date', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find upcoming exams for a user
     */
    public function findUpcomingExams(User $user, int $limit = 10): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('e')
            ->where('e.type = :type')
            ->andWhere('e.date >= :now')
            ->andWhere('e.creator = :user OR JSON_CONTAINS(e.attendees, :userId) = 1 OR e.isPublic = true')
            ->setParameter('type', 'exam')
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->setParameter('userId', $user->getId())
            ->orderBy('e.date', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find vacation periods
     */
    public function findVacations(\DateTime $startDate = null, \DateTime $endDate = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.type = :type')
            ->setParameter('type', 'vacation');

        if ($startDate) {
            $qb->andWhere('e.endDate >= :startDate OR e.date >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('e.date <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        return $qb->orderBy('e.date', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find events for a specific month
     */
    public function findByMonth(int $year, int $month, User $user = null): array
    {
        $startDate = new \DateTime("$year-$month-01");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month')->setTime(23, 59, 59);

        return $this->findByDateRange($startDate, $endDate, $user);
    }

    /**
     * Find events created by a user
     */
    public function findByCreator(User $creator): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.creator = :creator')
            ->setParameter('creator', $creator)
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find public events
     */
    public function findPublicEvents(\DateTime $startDate = null, \DateTime $endDate = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.isPublic = true');

        if ($startDate) {
            $qb->andWhere('e.date >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('e.date <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        return $qb->orderBy('e.date', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find events that need notification
     */
    public function findEventsNeedingNotification(\DateTime $notificationTime): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.notificationSent = false')
            ->andWhere('e.date <= :notificationTime')
            ->andWhere('e.date >= :now')
            ->setParameter('notificationTime', $notificationTime)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    /**
     * Search events by title or description
     */
    public function searchEvents(string $query, User $user = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.titre LIKE :query OR e.description LIKE :query')
            ->setParameter('query', "%$query%");

        if ($user) {
            $qb->andWhere('e.creator = :user OR JSON_CONTAINS(e.attendees, :userId) = 1 OR e.isPublic = true')
               ->setParameter('user', $user)
               ->setParameter('userId', $user->getId());
        } else {
            $qb->andWhere('e.isPublic = true');
        }

        return $qb->orderBy('e.date', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find recurring events that need to be generated
     */
    public function findRecurringEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.isRecurring = true')
            ->andWhere('e.recurringEndDate IS NULL OR e.recurringEndDate >= :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }
}
