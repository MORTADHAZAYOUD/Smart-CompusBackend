<?php

// src/Repository/StudentRepository.php
namespace App\Repository;

use App\Entity\Student;
use App\Entity\Classe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Student>
 */
class StudentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Student::class);
    }

    /**
     * Trouve les étudiants par classe
     */
    public function findByClasse(Classe $classe): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.classe = :classe')
            ->setParameter('classe', $classe)
            ->orderBy('s.lastname', 'ASC')
            ->addOrderBy('s.firstname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les étudiants par parent
     */
    public function findByParent($parent): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('s.lastname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche étudiants par numéro étudiant
     */
    public function findByNumStudent(string $numStudent): ?Student
    {
        return $this->createQueryBuilder('s')
            ->where('s.numStudent = :numStudent')
            ->setParameter('numStudent', $numStudent)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Étudiants par tranche d'âge
     */
    public function findByAgeRange(int $minAge, int $maxAge): array
    {
        $maxDate = new \DateTime();
        $maxDate->sub(new \DateInterval('P'.$minAge.'Y'));
        
        $minDate = new \DateTime();
        $minDate->sub(new \DateInterval('P'.$maxAge.'Y'));

        return $this->createQueryBuilder('s')
            ->where('s.dateNaissance BETWEEN :minDate AND :maxDate')
            ->setParameter('minDate', $minDate)
            ->setParameter('maxDate', $maxDate)
            ->orderBy('s.dateNaissance', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques par classe
     */
    public function getStudentStatsByClasse(): array
    {
        return $this->createQueryBuilder('s')
            ->select('c.nom as classe, COUNT(s.id) as nombre_etudiants')
            ->leftJoin('s.classe', 'c')
            ->groupBy('c.id')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Étudiants sans parent assigné
     */
    public function findOrphans(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.parent IS NULL')
            ->orderBy('s.lastname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Top étudiants par moyenne (nécessite relation avec notes)
     */
    public function findTopStudents(int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->select('s', 'AVG(n.valeur) as moyenne')
            ->leftJoin('s.notes', 'n')
            ->groupBy('s.id')
            ->orderBy('moyenne', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}