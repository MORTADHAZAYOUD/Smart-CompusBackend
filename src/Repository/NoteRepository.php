<?php

namespace App\Repository;

use App\Entity\Note;
use App\Entity\User;
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

    public function save(Note $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Note $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouver les notes d'un étudiant
     */
    public function findByEtudiant(User $etudiant): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->orderBy('n.dateAttribution', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les notes d'une séance
     */
    public function findBySeance(Seance $seance): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.seance = :seance')
            ->setParameter('seance', $seance)
            ->orderBy('n.valeur', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculer la moyenne d'un étudiant
     */
    public function calculateStudentAverage(User $etudiant): ?float
    {
        $result = $this->createQueryBuilder('n')
            ->select('AVG(n.valeur) as moyenne')
            ->andWhere('n.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : null;
    }

    /**
     * Calculer la moyenne d'une classe pour une séance
     */
    public function calculateSeanceAverage(Seance $seance): ?float
    {
        $result = $this->createQueryBuilder('n')
            ->select('AVG(n.valeur) as moyenne')
            ->andWhere('n.seance = :seance')
            ->setParameter('seance', $seance)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : null;
    }

    /**
     * Trouver les meilleures notes
     */
    public function findTopNotes(int $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->orderBy('n.valeur', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
