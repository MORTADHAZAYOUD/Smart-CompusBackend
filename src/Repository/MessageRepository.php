<?php

// src/Repository/MessageRepository.php
namespace App\Repository;

use App\Entity\Message;
use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findLatestByConversation(Conversation $conversation, int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conv')
            ->setParameter('conv', $conversation)
            ->orderBy('m.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}