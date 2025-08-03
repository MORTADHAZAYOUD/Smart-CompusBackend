<?php
// src/Repository/StatistiqueRepository.php
namespace App\Repository;

use App\Entity\Statistique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StatistiqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Statistique::class);
    }

    public function getStatistiquesByType(string $type): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }
}
