<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function save(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouver les messages reçus par un utilisateur
     */
    public function findReceivedByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.destinataire = :user')
            ->setParameter('user', $user)
            ->orderBy('m.dateEnvoi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les messages envoyés par un utilisateur
     */
    public function findSentByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.expediteur = :user')
            ->setParameter('user', $user)
            ->orderBy('m.dateEnvoi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les messages non lus d'un utilisateur
     */
    public function findUnreadByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.destinataire = :user')
            ->andWhere('m.lu = false')
            ->setParameter('user', $user)
            ->orderBy('m.dateEnvoi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter les messages non lus d'un utilisateur
     */
    public function countUnreadByUser(User $user): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.destinataire = :user')
            ->andWhere('m.lu = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouver la conversation entre deux utilisateurs
     */
    public function findConversationBetweenUsers(User $user1, User $user2): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere(
                $this->createQueryBuilder('m')->expr()->orX(
                    $this->createQueryBuilder('m')->expr()->andX(
                        'm.expediteur = :user1',
                        'm.destinataire = :user2'
                    ),
                    $this->createQueryBuilder('m')->expr()->andX(
                        'm.expediteur = :user2',
                        'm.destinataire = :user1'
                    )
                )
            )
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.dateEnvoi', 'ASC')
            ->getQuery()
            ->getResult();
    }
}