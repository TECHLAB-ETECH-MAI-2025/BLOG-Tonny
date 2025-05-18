<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ArticleApiController extends AbstractController
{
    /**
     * Point d'entrée AJAX (GET) pour DataTables : liste paginée, triée, filtrée des articles.
     *
     * @param Request $request Les paramètres DataTables (draw, start, length, search, order, categoryId...)
     * @param ArticleRepository $articleRepository Accès aux articles
     * @return JsonResponse Réponse structurée pour DataTables
     */
    #[Route('/articles', name: 'api_articles_list', methods: ['GET'])]
    public function getArticles(Request $request, ArticleRepository $articleRepository): JsonResponse
    {
        $draw = $request->query->get('draw', 1);
        $start = $request->query->get('start', 0);
        $length = $request->query->get('length', 10);
        $search = $request->query->all('search')['value'] ?? '';
        $order = $request->query->all('order')[0] ?? null;
        $categoryId = $request->query->get('categoryId', null);

        $results = $articleRepository->findForDataTable($start, $length, $search, $order, $categoryId);
        $total = $articleRepository->countAll();
        $filtered = $articleRepository->countFiltered($search, $categoryId);

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
                'actions' => ''
            ];
        }

        return $this->json($data);
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