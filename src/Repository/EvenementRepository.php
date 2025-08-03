<?php
// src/Repository/EvenementRepository.php
namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.date >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function findBetweenDates(\DateTime $start, \DateTime $end): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.date BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }
}
