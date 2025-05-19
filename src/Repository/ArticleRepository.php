<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

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
        return $this->paginate($page, $limit);
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
        return $this->paginate($page, $limit, "createdAt", "DESC");
    }

    /**
     * Refactorisation de la logique de pagination
     *
     * @param int $page Le numéro de page à afficher
     * @param int $limit Le nombre d'articles par page
     * @param string|null $orderField Le nom de la colonne pour faire l'ordre
     * @param string|null $orderDirection La direction selon ASC ou DESC
     * @return Paginator L'objet de pagination avec les résultats
     */
    private function paginate(int $page, int $limit, string $orderField = null, string $orderDirection = null): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('a');

        if ($orderField !== null) {
            $queryBuilder->orderBy("a.$orderField", $orderDirection);
        }

        $query = $queryBuilder->getQuery();

        $paginator = new Paginator($query);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        return $paginator;
    }

    /**
     * Recherche des articles par titre (partiel ou entier)
     * @param string $query Le titre en question
     * @return array Tableau des résultats
     */
    public function searchByTitle(string $query, int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.title LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Fournit les articles pour DataTables (tri, recherche, pagination, filtrage par catégorie).
     *
     * @param int $start Décalage de départ pour la pagination (offset)
     * @param int $length Nombre d'articles à retourner (limit)
     * @param string $search Terme de recherche (titre ou contenu)
     * @param array|null $order Tableau pour le tri (ex: ['column'=>1, 'dir'=>'desc'])
     * @param string|null $categoryId Id de catégorie pour filtrer
     * @return Article[] Tableau d'articles correspondant aux critères
     */
    public function findForDataTable(int $start, int $length, string $search = '', ?array $order = null, ?string $categoryId = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.categories', 'c')
            ->addSelect('c');

        // Recherche texte (titre ou contenu)
        if (!empty($search)) {
            $qb->andWhere('a.title LIKE :search OR a.content LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Filtrage par catégorie si demandé
        if (!empty($categoryId)) {
            $qb->andWhere(':categoryId MEMBER OF a.categories')
                ->setParameter('categoryId', $categoryId);
        }

        // Tri dynamique selon la colonne demandée par DataTables
        if ($order) {
            $columnIndex = $order['column'] ?? 0;
            $direction = $order['dir'] ?? 'asc';

            switch ($columnIndex) {
                case 0:
                    $qb->orderBy('a.id', $direction);
                    break;
                case 1:
                    $qb->orderBy('a.title', $direction);
                    break;
                case 2:
                    $qb->orderBy('a.content', $direction);
                    break;
                case 4:
                    $qb->orderBy('a.createdAt', $direction);
                    break;
                default:
                    $qb->orderBy('a.createdAt', 'DESC');
            }
        } else {
            $qb->orderBy('a.createdAt', 'DESC');
        }

        // Pagination (offset et limit)
        return $qb->setFirstResult($start)
            ->setMaxResults($length)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte tous les articles (pour DataTables, total général).
     *
     * @return int Nombre total d'articles
     */
    public function countAll(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les articles filtrés (après recherche/catégorie).
     *
     * @param string $search Terme de recherche
     * @param string|null $categoryId Id de catégorie pour filtrer
     * @return int Nombre d'articles filtrés
     */
    public function countFiltered(string $search = '', ?string $categoryId = null): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)');

        if (!empty($search)) {
            $qb->andWhere('a.title LIKE :search OR a.content LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if (!empty($categoryId)) {
            $qb->leftJoin('a.categories', 'c')
                ->andWhere(':categoryId MEMBER OF a.categories')
                ->setParameter('categoryId', $categoryId);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}