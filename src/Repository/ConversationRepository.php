<?php
namespace App\Repository;
use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 *
 * @method Conversation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Conversation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Conversation[]    findAll()
 * @method Conversation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * Trouve toutes les conversations auxquelles participe un utilisateur
     *
     * @param User $user
     * @return Conversation[]
     */
    public function findConversationsForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.participants', 'p')
            ->where('p.id = :userId')
            ->andWhere('c.active = :active')
            ->setParameter('userId', $user->getId())
            ->setParameter('active', true)
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une conversation entre deux utilisateurs spécifiques
     *
     * @param User $user1
     * @param User $user2
     * @return Conversation|null
     */
    public function findConversationBetweenUsers(User $user1, User $user2): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.participants', 'p1')
            ->innerJoin('c.participants', 'p2')
            ->where('p1.id = :user1Id')
            ->andWhere('p2.id = :user2Id')
            ->andWhere('c.active = :active')
            ->setParameter('user1Id', $user1->getId())
            ->setParameter('user2Id', $user2->getId())
            ->setParameter('active', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les conversations avec un nombre spécifique de participants
     *
     * @param User $user
     * @param int $participantCount
     * @return Conversation[]
     */
    public function findConversationsForUserByParticipantCount(User $user, int $participantCount): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.participants', 'p')
            ->where('p.id = :userId')
            ->andWhere('c.active = :active')
            ->andWhere('SIZE(c.participants) = :participantCount')
            ->setParameter('userId', $user->getId())
            ->setParameter('active', true)
            ->setParameter('participantCount', $participantCount)
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les conversations récentes d'un utilisateur (avec limite)
     *
     * @param User $user
     * @param int $limit
     * @return Conversation[]
     */
    public function findRecentConversationsForUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.participants', 'p')
            ->where('p.id = :userId')
            ->andWhere('c.active = :active')
            ->setParameter('userId', $user->getId())
            ->setParameter('active', true)
            ->orderBy('c.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de conversations actives d'un utilisateur
     *
     * @param User $user
     * @return int
     */
    public function countActiveConversationsForUser(User $user): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->innerJoin('c.participants', 'p')
            ->where('p.id = :userId')
            ->andWhere('c.active = :active')
            ->setParameter('userId', $user->getId())
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les conversations par titre (recherche)
     *
     * @param User $user
     * @param string $searchTerm
     * @return Conversation[]
     */
    public function searchConversationsByTitle(User $user, string $searchTerm): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.participants', 'p')
            ->where('p.id = :userId')
            ->andWhere('c.active = :active')
            ->andWhere('c.titre LIKE :searchTerm')
            ->setParameter('userId', $user->getId())
            ->setParameter('active', true)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}