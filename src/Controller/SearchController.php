<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    /*
     * Rechercher un article
     *
     * */
    #[Route('/search', name: 'app_search_ajax', methods: ['GET'])]
    public function ajaxSearch(Request $request, ArticleRepository $articleRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        $articles = $articleRepository->searchByTitle($query);

        $results = [];
        foreach ($articles as $article) {
            $results[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'url' => $this->generateUrl('app_article_show', ['id' => $article->getId()]),
                'excerpt' => substr($article->getContent(), 0, 100) . '...'
            ];
        }

        return $this->json($results);
    }


}