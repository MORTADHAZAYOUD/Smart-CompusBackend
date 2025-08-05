<?php

namespace App\Repository;

use App\Entity\Calendrier;
use App\Entity\Classe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Calendrier>
 */
class CalendrierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Calendrier::class);
    }

    public function save(Calendrier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Calendrier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouver le calendrier par classe et semaine
     */
    public function findByClasseAndWeek(Classe $classe, int $semaine, int $annee): ?Calendrier
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.classe = :classe')
            ->andWhere('c.semaine = :semaine')
            ->andWhere('c.annee = :annee')
            ->setParameter('classe', $classe)
            ->setParameter('semaine', $semaine)
            ->setParameter('annee', $annee)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouver les calendriers d'une classe
     */
    public function findByClasse(Classe $classe): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.classe = :classe')
            ->setParameter('classe', $classe)
            ->orderBy('c.annee', 'DESC')
            ->addOrderBy('c.semaine', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver le calendrier de la semaine courante
     */
    public function findCurrentWeekCalendars(): array
    {
        $currentWeek = (int) date('W');
        $currentYear = (int) date('Y');

        return $this->createQueryBuilder('c')
            ->andWhere('c.semaine = :semaine')
            ->andWhere('c.annee = :annee')
            ->setParameter('semaine', $currentWeek)
            ->setParameter('annee', $currentYear)
            ->getQuery()
            ->getResult();
    }
}