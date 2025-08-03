<?php    
// src/Repository/NoteRepository.php
namespace App\Repository;

use App\Entity\Note;
use App\Entity\Student;
use App\Entity\Seance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    /**
     * Calcule la moyenne générale
     */
    public function calculateGlobalAverage(): float
    {
        $result = $this->createQueryBuilder('n')
            ->select('AVG(n.valeur)')
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ?? 0;
    }

    /**
     * Moyennes par classe
     */
    public function getAveragesByClass(): array
    {
        return $this->createQueryBuilder('n')
            ->select('c.nom as classe, AVG(n.valeur) as moyenne, COUNT(n.id) as nb_notes')
            ->join('n.student', 's')
            ->join('s.classe', 'c')
            ->groupBy('c.id')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques de notes pour une classe
     */
    public function getClassGradeStats(int $classeId): array
    {
        return $this->createQueryBuilder('n')
            ->select('
                COUNT(n.id) as total_notes,
                AVG(n.valeur) as moyenne,
                MIN(n.valeur) as note_min,
                MAX(n.valeur) as note_max,
                COUNT(CASE WHEN n.valeur >= 10 THEN 1 END) as notes_positives,
                COUNT(CASE WHEN n.valeur < 10 THEN 1 END) as notes_negatives
            ')
            ->join('n.student', 's')
            ->where('s.classe = :classeId')
            ->setParameter('classeId', $classeId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Notes d'un étudiant
     */
    public function findByStudent(Student $student): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.student = :student')
            ->setParameter('student', $student)
            ->join('n.seance', 's')
            ->orderBy('s.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Notes pour une séance
     */
    public function findBySeance(Seance $seance): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.seance = :seance')
            ->setParameter('seance', $seance)
            ->join('n.student', 's')
            ->orderBy('s.lastname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Moyenne d'un étudiant
     */
    public function getStudentAverage(Student $student): float
    {
        $result = $this->createQueryBuilder('n')
            ->select('AVG(n.valeur)')
            ->where('n.student = :student')
            ->setParameter('student', $student)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ?? 0;
    }

    /**
     * Top étudiants par moyenne
     */
    public function findTopStudents(int $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->select('s', 'AVG(n.valeur) as moyenne')
            ->join('n.student', 's')
            ->groupBy('s.id')
            ->orderBy('moyenne', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Distribution des notes par tranche
     */
    public function getGradeDistribution(): array
    {
        return $this->createQueryBuilder('n')
            ->select('
                COUNT(CASE WHEN n.valeur >= 0 AND n.valeur < 5 THEN 1 END) as tranche_0_5,
                COUNT(CASE WHEN n.valeur >= 5 AND n.valeur < 10 THEN 1 END) as tranche_5_10,
                COUNT(CASE WHEN n.valeur >= 10 AND n.valeur < 15 THEN 1 END) as tranche_10_15,
                COUNT(CASE WHEN n.valeur >= 15 AND n.valeur <= 20 THEN 1 END) as tranche_15_20
            ')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Notes récentes
     */
    public function findRecentNotes(int $limit = 20): array
    {
        return $this->createQueryBuilder('n')
            ->join('n.seance', 's')
            ->orderBy('s.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Évolution des notes d'un étudiant
     */
    public function findStudentGradeEvolution(Student $student): array
    {
        return $this->createQueryBuilder('n')
            ->select('n.valeur, s.date, s.type')
            ->join('n.seance', 's')
            ->where('n.student = :student')
            ->setParameter('student', $student)
            ->orderBy('s.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
