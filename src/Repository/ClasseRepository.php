<?php
// src/Repository/ClasseRepository.php
namespace App\Repository;

use App\Entity\Classe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Classe>
 */
class ClasseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Classe::class);
    }

    /**
     * Trouve les classes avec le nombre d'étudiants
     */
    public function findWithStudentCount(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'COUNT(s.id) as studentCount')
            ->leftJoin('c.Student', 's')
            ->groupBy('c.id')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Classes par niveau (si vous avez une logique de niveau)
     */
    public function findByNiveau(string $niveau): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.nom LIKE :niveau')
            ->setParameter('niveau', $niveau.'%')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Classe avec ses étudiants et séances
     */
    public function findCompleteInfo(int $id): ?Classe
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.Student', 's')
            ->leftJoin('c.seances', 'se')
            ->addSelect('s', 'se')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Classes les plus peuplées
     */
    public function findMostPopulated(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'COUNT(s.id) as studentCount')
            ->leftJoin('c.Student', 's')
            ->groupBy('c.id')
            ->orderBy('studentCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Classes vides (sans étudiants)
     */
    public function findEmptyClasses(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.Student', 's')
            ->where('s.id IS NULL')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
