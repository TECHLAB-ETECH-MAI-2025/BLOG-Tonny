<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
      public function findUserByEmailOrUsername(string $usernameOrEmail): ?User{
        return $this->createQueryBuilder('user')
            ->where('user.email = :identifier')
            ->orWhere('user.username = :identifier')
            ->setParameter('identifier', $usernameOrEmail)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    /**
     * Trouve tous les utilisateurs sauf celui spécifié
     */
    public function findAllExcept(User $excludeUser): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.id != :excludeId')
            ->setParameter('excludeId', $excludeUser->getId())
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }
    /**
     * Validation stricte du mot de passe
     */
    public static function validateStrictPassword(string $password): ?array
    {
        if (empty($password)) {
            return [
                'error' => 'Veuillez entrer un mot de passe',
                'code' => 'PASSWORD_REQUIRED'
            ];
        }

        if (strlen($password) < 8) {
            return [
                'error' => 'Votre mot de passe doit contenir au moins 8 caractères',
                'code' => 'PASSWORD_TOO_SHORT'
            ];
        }

        if (strlen($password) > 4096) {
            return [
                'error' => 'Votre mot de passe est trop long',
                'code' => 'PASSWORD_TOO_LONG'
            ];
        }

        // Vérification de la complexité : au moins une majuscule, une minuscule, un chiffre et un caractère spécial
        if (!preg_match('/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$ %^&*-]).{8,}$/', $password)) {
            return [
                'error' => 'Votre mot de passe doit contenir au moins une lettre majuscule, une lettre minuscule, un chiffre et un caractère spécial',
                'code' => 'PASSWORD_NOT_COMPLEX_ENOUGH'
            ];
        }

        return null;
    }
    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
