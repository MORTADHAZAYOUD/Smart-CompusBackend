<?php

namespace App\Repository;

use App\Entity\Administrator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Administrator>
 */
class AdministratorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Administrator::class);
    }
    /**
     * Finds administrators by privilege.
     *
     * @param string $privilege
     * @return Administrator[]
     */
    public function findByPrivilege(string $privilege): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere(':privilege MEMBER OF a.privileges')
            ->setParameter('privilege', $privilege)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns statistics of administrators grouped by privilege.
     *
     * @return array
     */
    public function countByPrivilege(): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('p AS privilege, COUNT(a.id) AS count')
            ->from(Administrator::class, 'a')
            ->join('a.privileges', 'p')
            ->groupBy('p');

        return $qb->getQuery()->getResult();
    }
    //    /**
    //     * @return Administrator[] Returns an array of Administrator objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Administrator
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
