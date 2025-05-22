<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Service\ArticleService;
use App\Entity\Article;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class ArticleApiController extends AbstractController
{
    /**
     * Point d'entrée AJAX (GET) pour DataTables : liste paginée, triée, filtrée des articles actifs.
     *
     * @param Request $request Les paramètres DataTables (draw, start, length, search, order, categoryId...)
     * @param ArticleRepository $articleRepository Accès aux articles
     * @return JsonResponse Réponse structurée pour DataTables
     */
    #[Route('/articles', name: 'api_articles_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getArticles(Request $request, ArticleRepository $articleRepository): JsonResponse
    {
        $draw = $request->query->get('draw', 1);
        $start = $request->query->get('start', 0);
        $length = $request->query->get('length', 10);
        $search = $request->query->all('search')['value'] ?? '';
        $order = $request->query->all('order')[0] ?? null;
        $categoryId = $request->query->get('categoryId', null);
        $includeDeleted = $request->query->getBoolean('includeDeleted', false);

        $results = $articleRepository->findForDataTable($start, $length, $search, $order, $categoryId, $includeDeleted);
        $total = $articleRepository->countAll($includeDeleted);
        $filtered = $articleRepository->countFiltered($search, $categoryId, $includeDeleted);

        // Structure conforme à l'attente de DataTables côté JS
        $data = [
            'draw' => (int)$draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => []
        ];

        foreach ($results as $article) {
            $data['data'][] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'content' => $article->getContent(),
                'categories' => $article->getCategories()->map(fn($c) => [
                    'id' => $c->getId(),
                    'name' => $c->getName()
                ])->toArray(),
                'createdAt' => $article->getCreatedAt()->format('Y-m-d H:i:s'),
                'deletedAt' => $article->getDeletedAt()?->format('Y-m-d H:i:s'),
                'isDeleted' => $article->isDeleted(),
                'actions' => ''
            ];
        }

        return $this->json($data);
    }

    /**
     * Point d'entrée AJAX (GET) pour DataTables : liste paginée des articles supprimés uniquement.
     *
     * @param Request $request Les paramètres DataTables
     * @param ArticleRepository $articleRepository Accès aux articles
     * @return JsonResponse Réponse structurée pour DataTables
     */
    #[Route('/articles/deleted', name: 'api_articles_deleted_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getDeletedArticles(Request $request, ArticleRepository $articleRepository): JsonResponse
    {
        $draw = $request->query->get('draw', 1);
        $start = $request->query->get('start', 0);
        $length = $request->query->get('length', 10);

        // Pour les articles supprimés, on utilise une méthode spécifique
        $paginator = $articleRepository->paginateDeletedArticles(
            intval($start / $length) + 1,
            $length
        );

        $results = iterator_to_array($paginator);
        $total = $articleRepository->countDeleted();

        $data = [
            'draw' => (int)$draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => []
        ];

        foreach ($results as $article) {
            $data['data'][] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'content' => $article->getContent(),
                'categories' => $article->getCategories()->map(fn($c) => [
                    'id' => $c->getId(),
                    'name' => $c->getName()
                ])->toArray(),
                'createdAt' => $article->getCreatedAt()->format('Y-m-d H:i:s'),
                'deletedAt' => $article->getDeletedAt()->format('Y-m-d H:i:s'),
                'isDeleted' => true,
                'actions' => ''
            ];
        }

        return $this->json($data);
    }

    /**
     * Supprime un article (soft delete) via AJAX
     *
     * @param Article $article L'article à supprimer
     * @param ArticleService $articleService Service pour gérer les articles
     * @return JsonResponse
     */
    #[Route('/articles/{id}/delete', name: 'api_article_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteArticle(Article $article, ArticleService $articleService): JsonResponse
    {
        try {
            if ($article->isDeleted()) {
                return $this->json(['success' => false, 'message' => 'Article déjà supprimé'], 400);
            }

            $articleService->deleteArticle($article);

            return $this->json([
                'success' => true,
                'message' => 'Article supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaure un article supprimé via AJAX
     *
     * @param Article $article L'article à restaurer
     * @param ArticleService $articleService Service pour gérer les articles
     * @return JsonResponse
     */
    #[Route('/articles/{id}/restore', name: 'api_article_restore', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function restoreArticle(Article $article, ArticleService $articleService): JsonResponse
    {
        try {
            if (!$article->isDeleted()) {
                return $this->json(['success' => false, 'message' => 'Article non supprimé'], 400);
            }

            $articleService->restoreArticle($article);

            return $this->json([
                'success' => true,
                'message' => 'Article restauré avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime définitivement un article via AJAX
     *
     * @param Article $article L'article à supprimer définitivement
     * @param ArticleService $articleService Service pour gérer les articles
     * @return JsonResponse
     */
    #[Route('/articles/{id}/permanent-delete', name: 'api_article_permanent_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function permanentDeleteArticle(Article $article, ArticleService $articleService): JsonResponse
    {
        try {
            $articleService->permanentlyDeleteArticle($article);

            return $this->json([
                'success' => true,
                'message' => 'Article supprimé définitivement'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression définitive: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Point d'entrée AJAX (GET) pour récupérer la liste des catégories (pour les filtres côté front).
     *
     * @param CategoryRepository $categoryRepository Accès aux catégories
     * @return JsonResponse Liste des catégories
     */
    #[Route('/categories', name: 'api_categories_list', methods: ['GET'])]
    public function getCategories(CategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findAllOrdered();

        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'id' => $category->getId(),
                'name' => $category->getName()
            ];
        }
        return $this->json($data);
    }
}