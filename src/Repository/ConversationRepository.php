<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    public function save(Conversation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Conversation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouver les conversations d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->andWhere('p = :user')
            ->andWhere('c.active = true')
            ->setParameter('user', $user)
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver une conversation entre des utilisateurs spécifiques
     */
    public function findByParticipants(array $users): ?Conversation
    {
        if (count($users) < 2) {
            return null;
        }

        $qb = $this->createQueryBuilder('c')
            ->join('c.participants', 'p');

        foreach ($users as $index => $user) {
            $qb->andWhere(':user' . $index . ' MEMBER OF c.participants')
                ->setParameter('user' . $index, $user);
        }

        return $qb->getQuery()
            ->getOneOrNullResult();
    }
}