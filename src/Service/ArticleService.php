<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Article;
use App\Entity\Category;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use InvalidArgumentException; // Added for missing fields
use Symfony\Component\Validator\Exception\ValidationFailedException; // For validation errors

class ArticleService
{
    private EntityManagerInterface $entityManager;
    private ArticleRepository $articleRepository;
    private ValidatorInterface $validator;
    private CategoryRepository $categoryRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ArticleRepository $articleRepository,
        ValidatorInterface $validator,
        CategoryRepository $categoryRepository
    ) {
        $this->entityManager = $entityManager;
        $this->articleRepository = $articleRepository;
        $this->validator = $validator;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Récupère les articles de manière paginée
     *
     * @param int $page La page courante
     * @param int $limit Nombre d'articles par page
     * @param bool $includeDeleted Inclure les articles supprimés
     * @return array Tableau contenant les articles paginés et les informations de pagination
     */
    public function getPaginatedArticles(int $page = 1, int $limit = 10, bool $includeDeleted = false): array
    {
        $articles = $this->articleRepository->paginateArticlesDesc($page, $limit, $includeDeleted);
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
     */
    public function updateArticle(Article $article): void
    {
        $this->entityManager->flush();
    }

    /**
     * Supprime un article (soft delete)
     *
     * @param Article $article L'article à supprimer
     */
    public function deleteArticle(Article $article): void
    {
        $article->setDeletedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * Restaure un article supprimé
     *
     * @param Article $article L'article à restaurer
     */
    public function restoreArticle(Article $article): void
    {
        $article->setDeletedAt(null);
        $this->entityManager->flush();
    }

    /**
     * Supprime définitivement un article
     *
     * @param Article $article L'article à supprimer définitivement
     */
    public function permanentlyDeleteArticle(Article $article): void
    {
        $this->entityManager->remove($article);
        $this->entityManager->flush();
    }

    /**
     * Creates a new article from API data.
     *
     * @param array $data Deserialized JSON data
     * @return Article The created article
     * @throws InvalidArgumentException If required fields are missing or data is invalid
     * @throws ValidationFailedException If validation fails
     */
    public function createArticleFromApi(array $data): Article
    {
        if (empty($data['title']) || empty($data['content'])) {
            throw new InvalidArgumentException('Required fields "title" and "content" are missing.');
        }

        $article = new Article();
        $article->setTitle($data['title']);
        $article->setContent($data['content']);
        // Optionally set other fields like author if available and handled

        if (isset($data['categories']) && is_array($data['categories'])) {
            foreach ($data['categories'] as $categoryId) {
                $category = $this->categoryRepository->find($categoryId);
                if ($category) {
                    $article->addCategory($category);
                }
                // Optionally handle else: throw exception or log if category not found
            }
        }

        $violations = $this->validator->validate($article);
        if (count($violations) > 0) {
            // TODO: Consider a custom ValidationException here
            throw new ValidationFailedException($article, $violations);
        }

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        return $article;
    }

    /**
     * Updates an existing article from API data.
     *
     * @param Article $article The article to update
     * @param array $data Deserialized JSON data
     * @return Article The updated article
     * @throws \LogicException If trying to update a soft-deleted article
     * @throws InvalidArgumentException If data is invalid
     * @throws ValidationFailedException If validation fails
     */
    public function updateArticleFromApi(Article $article, array $data): Article
    {
        if ($article->getDeletedAt() !== null) {
            throw new \LogicException('Cannot update a soft-deleted article.');
        }

        if (isset($data['title'])) {
            $article->setTitle($data['title']);
        }

        if (isset($data['content'])) {
            $article->setContent($data['content']);
        }

        if (isset($data['categories']) && is_array($data['categories'])) {
            // Clear existing categories
            foreach ($article->getCategories() as $category) {
                $article->removeCategory($category);
            }
            // Add new categories
            foreach ($data['categories'] as $categoryId) {
                $category = $this->categoryRepository->find($categoryId);
                if ($category) {
                    $article->addCategory($category);
                }
                // Optionally handle else: throw exception or log if category not found
            }
        }

        $violations = $this->validator->validate($article);
        if (count($violations) > 0) {
            // TODO: Consider a custom ValidationException here
            throw new ValidationFailedException($article, $violations);
        }

        $this->entityManager->flush();

        return $article;
    }
}