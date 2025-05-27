<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Récupère la conversation entre deux utilisateurs.
     *
     * @param User $user1 Premier utilisateur.
     * @param User $user2 Deuxième utilisateur.
     * @param int $limit Limite du nombre de messages à récupérer.
     * @return array Tableau des messages de la conversation.
     */
    public function findConversation(User $user1, User $user2, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.receiver = :user2) OR (m.sender = :user2 AND m.receiver = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPaginatedConversation(User $currentUser, User $otherUser, int $page, int $limit): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :currentUser AND m.receiver = :otherUser) OR (m.sender = :otherUser AND m.receiver = :currentUser)')
            ->setParameter('currentUser', $currentUser)
            ->setParameter('otherUser', $otherUser)
            ->orderBy('m.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countConversationMessages(User $currentUser, User $otherUser): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('(m.sender = :currentUser AND m.receiver = :otherUser) OR (m.sender = :otherUser AND m.receiver = :currentUser)')
            ->setParameter('currentUser', $currentUser)
            ->setParameter('otherUser', $otherUser)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLastMessageBetweenUsers(User $user1, User $user2): ?Message
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.receiver = :user2) OR (m.sender = :user2 AND m.receiver = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}