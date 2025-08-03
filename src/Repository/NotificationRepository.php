<?php

// src/Repository/NotificationRepository.php
namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findUnseenByUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.destinataire = :user')
            ->andWhere('n.vue = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}