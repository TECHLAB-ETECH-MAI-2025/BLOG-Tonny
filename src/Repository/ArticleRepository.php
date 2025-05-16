<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Pagine les articles en ordre chronologique.
     * Renvoie un objet Paginator contenant les articles pour la page demandée.
     *
     * @param int $page Le numéro de page à afficher
     * @param int $limit Le nombre d'articles par page
     * @return Paginator L'objet de pagination avec les résultats
     */
    public function paginateArticles(int $page, int $limit): Paginator
    {
        return new Paginator($this->createQueryBuilder('r')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->setHint(Paginator::HINT_ENABLE_DISTINCT, false),
            false
        );
    }

    /**
     * Pagine les articles par ordre décroissant de date de création.
     * Permet d'afficher les articles les plus récents en premier.
     *
     * @param int $page Le numéro de page à afficher
     * @param int $limit Le nombre d'articles par page
     * @return Paginator L'objet de pagination avec les résultats
     */
    public function paginateArticlesDesc(int $page, int $limit): Paginator
    {
        $query = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery();

        $paginator = new Paginator($query);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        return $paginator;
    }

    //    /**
    //     * @return Article[] Returns an array of Article objects
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

    //    public function findOneBySomeField($value): ?Article
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
