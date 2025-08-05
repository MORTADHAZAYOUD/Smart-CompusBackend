<?php

// src/Repository/SeanceRepository.php
namespace App\Repository;

use App\Entity\Seance;
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

    public function save(Seance $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Seance $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouver les séances par classe
     */
    public function findByClasse($classe): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.classe = :classe')
            ->setParameter('classe', $classe)
            ->orderBy('s.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les séances par enseignant
     */
    public function findByEnseignant($enseignant): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.enseignant = :enseignant')
            ->setParameter('enseignant', $enseignant)
            ->orderBy('s.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les séances d'aujourd'hui
     */
    public function findTodaySeances(): array
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        return $this->createQueryBuilder('s')
            ->andWhere('s.dateDebut >= :today')
            ->andWhere('s.dateDebut < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('s.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les séances par période
     */
    public function findByPeriod(\DateTime $start, \DateTime $end): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.dateDebut >= :start')
            ->andWhere('s.dateDebut <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('s.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
