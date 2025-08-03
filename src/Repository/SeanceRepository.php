<?php

// src/Repository/SeanceRepository.php
namespace App\Repository;

use App\Entity\Seance;
use App\Entity\Classe;
use App\Entity\Teacher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Seance>
 */
class SeanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seance::class);
    }

    /**
     * Trouve les séances entre deux dates
     */
    public function findBetweenDates(\DateTime $start, \DateTime $end, ?int $classeId = null, ?int $teacherId = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.date BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('s.date', 'ASC');
        
        if ($classeId) {
            $qb->andWhere('s.classe = :classe')
               ->setParameter('classe', $classeId);
        }
        
        if ($teacherId) {
            $qb->andWhere('s.enseignant = :teacher')
               ->setParameter('teacher', $teacherId);
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Séances du jour
     */
    public function findTodaySeances(): array
    {
        $today = new \DateTime();
        $tomorrow = new \DateTime('tomorrow');
        
        return $this->createQueryBuilder('s')
            ->where('s.date >= :today')
            ->andWhere('s.date < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('s.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Séances par type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.type = :type')
            ->setParameter('type', $type)
            ->orderBy('s.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Séances par classe
     */
    public function findByClasse(Classe $classe): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.classe = :classe')
            ->setParameter('classe', $classe)
            ->orderBy('s.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Séances par enseignant
     */
    public function findByTeacher(Teacher $teacher): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.enseignant = :teacher')
            ->setParameter('teacher', $teacher)
            ->orderBy('s.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Séances à venir pour une classe
     */
    public function findUpcomingByClasse(Classe $classe, int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.classe = :classe')
            ->andWhere('s.date > :now')
            ->setParameter('classe', $classe)
            ->setParameter('now', new \DateTime())
            ->orderBy('s.date', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Séances en ligne vs présentiel
     */
    public function getSeanceStatsByMode(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.presentiel, COUNT(s.id) as count')
            ->groupBy('s.presentiel')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques par type
     */
    public function getSeanceStatsByType(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.type, COUNT(s.id) as count')
            ->groupBy('s.type')
            ->orderBy('s.type', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Planning hebdomadaire d'une classe
     */
    public function findWeeklySchedule(Classe $classe, \DateTime $weekStart): array
    {
        $weekEnd = clone $weekStart;
        $weekEnd->add(new \DateInterval('P7D'));

        return $this->createQueryBuilder('s')
            ->where('s.classe = :classe')
            ->andWhere('s.date BETWEEN :start AND :end')
            ->setParameter('classe', $classe)
            ->setParameter('start', $weekStart)
            ->setParameter('end', $weekEnd)
            ->orderBy('s.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
