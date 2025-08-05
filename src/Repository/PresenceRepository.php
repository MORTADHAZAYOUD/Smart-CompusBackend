<?php
// src/Repository/PresenceRepository.php
namespace App\Repository;

use App\Entity\Presence;
use App\Entity\User;
use App\Entity\Seance;
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

    public function save(Presence $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Presence $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouver les présences d'un étudiant
     */
    public function findByEtudiant(User $etudiant): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->orderBy('p.dateMarquage', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les présences d'une séance
     */
    public function findBySeance(Seance $seance): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.seance = :seance')
            ->setParameter('seance', $seance)
            ->orderBy('p.dateMarquage', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculer le taux de présence d'un étudiant
     */
    public function calculateAttendanceRate(User $etudiant): float
    {
        $total = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->getQuery()
            ->getSingleScalarResult();

        if ($total === 0) {
            return 0;
        }

        $present = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.etudiant = :etudiant')
            ->andWhere('p.status = :status')
            ->setParameter('etudiant', $etudiant)
            ->setParameter('status', 'present')
            ->getQuery()
            ->getSingleScalarResult();

        return ($present / $total) * 100;
    }

    /**
     * Trouver les absences non justifiées
     */
    public function findUnexcusedAbsences(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'absent')
            ->orderBy('p.dateMarquage', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
