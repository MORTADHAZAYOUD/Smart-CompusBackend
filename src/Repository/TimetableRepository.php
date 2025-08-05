<?php

namespace App\Repository;

use App\Entity\Timetable;
use App\Entity\User;
use App\Entity\Classe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Timetable>
 */
class TimetableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Timetable::class);
    }

    /**
     * Find timetables for a specific user
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find timetables for a specific class
     */
    public function findByClasse(Classe $classe): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.classe = :classe')
            ->setParameter('classe', $classe)
            ->orderBy('t.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find timetables for a specific day of the week
     */
    public function findByDayOfWeek(string $dayOfWeek): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.dayOfWeek = :dayOfWeek')
            ->setParameter('dayOfWeek', $dayOfWeek)
            ->orderBy('t.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find timetables within a date range
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.startTime >= :startDate')
            ->andWhere('t.endTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('t.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find upcoming timetables for a user
     */
    public function findUpcomingByUser(User $user, int $limit = 10): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.startTime >= :now')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->orderBy('t.startTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find timetables by type (class, exam, meeting, etc.)
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.type = :type')
            ->setParameter('type', $type)
            ->orderBy('t.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find weekly schedule for a user
     */
    public function findWeeklyScheduleByUser(User $user, \DateTime $startOfWeek): array
    {
        $endOfWeek = clone $startOfWeek;
        $endOfWeek->modify('+6 days')->setTime(23, 59, 59);
        
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.startTime >= :startOfWeek')
            ->andWhere('t.startTime <= :endOfWeek')
            ->setParameter('user', $user)
            ->setParameter('startOfWeek', $startOfWeek)
            ->setParameter('endOfWeek', $endOfWeek)
            ->orderBy('t.dayOfWeek', 'ASC')
            ->addOrderBy('t.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}