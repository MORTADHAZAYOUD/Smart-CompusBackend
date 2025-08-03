<?php
// src/Repository/PresenceRepository.php
namespace App\Repository;

use App\Entity\Presence;
use App\Entity\Student;
use App\Entity\Seance;
use App\Entity\Classe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Presence>
 */
class PresenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Presence::class);
    }

    /**
     * Calcule le taux de présence global
     */
    public function calculateGlobalAttendanceRate(): float
    {
        $total = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        if ($total == 0) return 0;
        
        $present = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.present = true')
            ->getQuery()
            ->getSingleScalarResult();
        
        return ($present / $total) * 100;
    }

    /**
     * Taux de présence par classe
     */
    public function getAttendanceRatesByClass(): array
    {
        return $this->createQueryBuilder('p')
            ->select('c.nom as classe, COUNT(p.id) as total, SUM(CASE WHEN p.present = true THEN 1 ELSE 0 END) as present')
            ->join('p.student', 's')
            ->join('s.classe', 'c')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques de présence pour une classe
     */
    public function getClassAttendanceStats(int $classeId): array
    {
        return $this->createQueryBuilder('p')
            ->select('
                COUNT(p.id) as total_presences,
                SUM(CASE WHEN p.present = true THEN 1 ELSE 0 END) as presences_marquees,
                SUM(CASE WHEN p.present = false THEN 1 ELSE 0 END) as absences,
                AVG(CASE WHEN p.present = true THEN 1 ELSE 0 END) * 100 as taux_presence
            ')
            ->join('p.student', 's')
            ->where('s.classe = :classeId')
            ->setParameter('classeId', $classeId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Présences d'un étudiant
     */
    public function findByStudent(Student $student): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.student = :student')
            ->setParameter('student', $student)
            ->join('p.seance', 's')
            ->orderBy('s.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Présences pour une séance
     */
    public function findBySeance(Seance $seance): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.seance = :seance')
            ->setParameter('seance', $seance)
            ->join('p.student', 's')
            ->orderBy('s.lastname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Taux de présence d'un étudiant
     */
    public function getStudentAttendanceRate(Student $student): float
    {
        $total = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.student = :student')
            ->setParameter('student', $student)
            ->getQuery()
            ->getSingleScalarResult();
        
        if ($total == 0) return 0;
        
        $present = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.student = :student')
            ->andWhere('p.present = true')
            ->setParameter('student', $student)
            ->getQuery()
            ->getSingleScalarResult();
        
        return ($present / $total) * 100;
    }

    /**
     * Étudiants les plus absents
     */
    public function findMostAbsentStudents(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->select('s', 'COUNT(p.id) as total_absences')
            ->join('p.student', 's')
            ->where('p.present = false')
            ->groupBy('s.id')
            ->orderBy('total_absences', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Présences par période
     */
    public function findByDateRange(\DateTime $start, \DateTime $end): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.seance', 's')
            ->where('s.date BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('s.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Présences récentes d'un étudiant
     */
    public function findRecentByStudent(Student $student, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.student = :student')
            ->setParameter('student', $student)
            ->join('p.seance', 's')
            ->orderBy('s.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
