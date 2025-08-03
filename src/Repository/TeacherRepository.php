<?php
// src/Repository/TeacherRepository.php
namespace App\Repository;

use App\Entity\Teacher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Teacher>
 */
class TeacherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Teacher::class);
    }

    /**
     * Trouve les enseignants par spécialité
     */
    public function findBySpecialite(string $specialite): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.specialite = :specialite')
            ->setParameter('specialite', $specialite)
            ->orderBy('t.lastname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Liste toutes les spécialités
     */
    public function getAllSpecialites(): array
    {
        return $this->createQueryBuilder('t')
            ->select('DISTINCT t.specialite')
            ->where('t.specialite IS NOT NULL')
            ->orderBy('t.specialite', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * Enseignants avec leurs séances
     */
    public function findWithSeances(): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.seances', 's')
            ->addSelect('s')
            ->orderBy('t.lastname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques par spécialité
     */
    public function getTeacherStatsBySpecialite(): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.specialite, COUNT(t.id) as nombre')
            ->where('t.specialite IS NOT NULL')
            ->groupBy('t.specialite')
            ->orderBy('t.specialite', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Enseignants les plus actifs (par nombre de séances)
     */
    public function findMostActiveTeachers(int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->select('t', 'COUNT(s.id) as nb_seances')
            ->leftJoin('t.seances', 's')
            ->groupBy('t.id')
            ->orderBy('nb_seances', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

