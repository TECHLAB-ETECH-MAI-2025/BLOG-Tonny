<?php

namespace App\Repository;

use App\Entity\Article;
use DateTimeImmutable;
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
     * Pagine les articles par ordre décroissant de date de création (excluant les supprimés par défaut).
     * Permet d'afficher les articles les plus récents en premier.
     *
     * @param int $page Le numéro de page à afficher
     * @param int $limit Le nombre d'articles par page
     * @param bool $includeDeleted Inclure les articles supprimés
     * @return Paginator L'objet de pagination avec les résultats
     */
    public function paginateArticlesDesc(int $page, int $limit, bool $includeDeleted = false): Paginator
    {
        return $this->paginate($page, $limit, "createdAt", "DESC", $includeDeleted);
    }

    /**
     * Refactorisation de la logique de pagination
     *
     * @param int $page Le numéro de page à afficher
     * @param int $limit Le nombre d'articles par page
     * @param string|null $orderField Le nom de la colonne pour faire l'ordre
     * @param string|null $orderDirection La direction selon ASC ou DESC
     * @param bool $includeDeleted Inclure les articles supprimés
     * @return Paginator L'objet de pagination avec les résultats
     */
    private function paginate(int $page, int $limit, string $orderField = null, string $orderDirection = null, bool $includeDeleted = false): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('a');

        if (!$includeDeleted) {
            $queryBuilder->andWhere('a.deletedAt IS NULL');
        }

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
     * Pagine uniquement les articles supprimés
     *
     * @param int $page
     * @param int $limit
     * @return Paginator
     */
    public function paginateDeletedArticles(int $page, int $limit): Paginator
    {
        $query = $this->createQueryBuilder('a')
            ->where('a.deletedAt IS NOT NULL')
            ->orderBy('a.deletedAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery();

        return new Paginator($query);
    }

    /**
     * Recherche des articles par titre (partiel ou entier)
     * @param string $query Le titre en question
     * @param int $limit Limite de résultats
     * @param bool $includeDeleted Inclure les articles supprimés
     * @return array Tableau des résultats
     */
    public function searchByTitle(string $query, int $limit = 5, bool $includeDeleted = false): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.title LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit);

        if (!$includeDeleted) {
            $qb->andWhere('a.deletedAt IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Fournit les articles pour DataTables (tri, recherche, pagination, filtrage par catégorie)
     *
     * @param int $start Décalage de départ pour la pagination (offset)
     * @param int $length Nombre d'articles à retourner (limit)
     * @param string $search Terme de recherche (titre ou contenu)
     * @param array|null $order Tableau pour le tri (ex: ['column'=>1, 'dir'=>'desc'])
     * @param string|null $categoryId Id de catégorie pour filtrer
     * @param bool $includeDeleted Inclure les articles supprimés
     * @return Article[] Tableau d'articles correspondant aux critères
     */
    public function findForDataTable(int $start, int $length, string $search = '', ?array $order = null, ?string $categoryId = null, bool $includeDeleted = false): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.categories', 'c')
            ->addSelect('c');

        if (!$includeDeleted) {
            $qb->andWhere('a.deletedAt IS NULL');
        }

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
     * Compte tous les articles actifs (pour DataTables, total général).
     *
     * @param bool $includeDeleted Inclure les articles supprimés dans le compte
     * @return int Nombre total d'articles
     */
    public function countAll(bool $includeDeleted = false): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)');

        if (!$includeDeleted) {
            $qb->where('a.deletedAt IS NULL');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Compte les articles filtrés (après recherche/catégorie)
     *
     * @param string $search Terme de recherche
     * @param string|null $categoryId Id de catégorie pour filtrer
     * @param bool $includeDeleted Inclure les articles supprimés
     * @return int Nombre d'articles filtrés
     */
    public function countFiltered(string $search = '', ?string $categoryId = null, bool $includeDeleted = false): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)');

        if (!$includeDeleted) {
            $qb->andWhere('a.deletedAt IS NULL');
        }

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

    /**
     * Compte les articles actifs
     *
     * @return int
     */
    public function countActive(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.deletedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les articles supprimés
     *
     * @return int
     */
    public function countDeleted(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.deletedAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Surcharge de la méthode findBy pour exclure les articles supprimés par défaut
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @param bool $includeDeleted
     * @return Article[]
     */
    public function findBy(array $criteria, ?array $orderBy = null, int $limit = null, int $offset = null, bool $includeDeleted = false): array
    {
        $qb = $this->createQueryBuilder('a');

        // Exclure les articles supprimés par défaut
        if (!$includeDeleted) {
            $qb->andWhere('a.deletedAt IS NULL');
        }

        // Appliquer les critères
        foreach ($criteria as $field => $value) {
            $qb->andWhere("a.$field = :$field")
                ->setParameter($field, $value);
        }

        // Appliquer l'ordre
        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy("a.$field", $direction);
            }
        }

        // Appliquer la limite et l'offset
        if ($limit) {
            $qb->setMaxResults($limit);
        }
        if ($offset) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Surcharge de la méthode findOneBy pour exclure les articles supprimés par défaut
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @param bool $includeDeleted
     * @return Article|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null, bool $includeDeleted = false): ?Article
    {
        $results = $this->findBy($criteria, $orderBy, 1, null, $includeDeleted);
        return $results[0] ?? null;
    }
}