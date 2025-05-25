<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Dépôt pour gérer les entités Message.
 */
class MessageRepository extends ServiceEntityRepository
{
    /**
     * Constructeur du dépôt de messages.
     *
     * @param ManagerRegistry $registry Registre du gestionnaire d'entités.
     */
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
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère le dernier message échangé entre deux utilisateurs.
     *
     * @param User $user1 Premier utilisateur.
     * @param User $user2 Deuxième utilisateur.
     * @return Message|null Le dernier message ou null s'il n'y en a pas.
     */
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
