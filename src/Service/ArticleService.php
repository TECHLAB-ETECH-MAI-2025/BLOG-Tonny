<?php

namespace App\Service;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;

class ArticleService
{
    private EntityManagerInterface $entityManager;
    private ArticleRepository $articleRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ArticleRepository $articleRepository
    ) {
        $this->entityManager = $entityManager;
        $this->articleRepository = $articleRepository;
    }

    /**
     * Récupère les articles de manière paginée
     *
     * @param int $page La page courante
     * @param int $limit Nombre d'articles par page
     * @return array Tableau contenant les articles paginés et les informations de pagination
     */
    public function getPaginatedArticles(int $page = 1, int $limit = 10): array
    {
        $articles = $this->articleRepository->paginateArticles($page, $limit);
        $maxPage = ceil($articles->count() / $limit);

        return [
            'articles' => $articles,
            'maxPage' => $maxPage,
            'page' => $page,
        ];
    }

    /**
     * Crée un nouvel article
     *
     * @param Article $article L'article à créer
     * @return void
     */
    public function createArticle(Article $article): void
    {
        $this->entityManager->persist($article);
        $this->entityManager->flush();
    }

    /**
     * Met à jour un article existant
     *
     * @param Article $article L'article à mettre à jour
     * @return void
     */
    public function updateArticle(Article $article): void
    {
        $this->entityManager->flush();
    }

    /**
     * Supprime un article
     *
     * @param Article $article L'article à supprimer
     * @return void
     */
    public function deleteArticle(Article $article): void
    {
        $this->entityManager->remove($article);
        $this->entityManager->flush();
    }
}