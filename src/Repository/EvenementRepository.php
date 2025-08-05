<?php
// src/Repository/EvenementRepository.php
namespace App\Repository;

use App\Entity\Evenement;
use App\Entity\Calendrier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    public function save(Evenement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Evenement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouver les événements par calendrier
     */
    public function findByCalendrier(Calendrier $calendrier): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.calendrier = :calendrier')
            ->setParameter('calendrier', $calendrier)
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les événements par type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.type = :type')
            ->setParameter('type', $type)
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les événements d'aujourd'hui
     */
    public function findTodayEvents(): array
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        return $this->createQueryBuilder('e')
            ->andWhere('e.dateDebut >= :today')
            ->andWhere('e.dateDebut < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les événements par période
     */
    public function findByPeriod(\DateTime $start, \DateTime $end): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.dateDebut >= :start')
            ->andWhere('e.dateDebut <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les événements à venir
     */
    public function findUpcomingEvents(int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.dateDebut > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.dateDebut', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
