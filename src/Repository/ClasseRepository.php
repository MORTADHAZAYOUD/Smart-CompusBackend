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

    public function save(Classe $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Classe $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouver toutes les classes avec leurs étudiants
     */
    public function findAllWithStudents(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.etudiants', 'e')
            ->addSelect('e')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les classes par enseignant
     */
    public function findByEnseignant($enseignant): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.enseignant = :enseignant')
            ->setParameter('enseignant', $enseignant)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
