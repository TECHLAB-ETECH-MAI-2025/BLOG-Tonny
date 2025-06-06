<?php

namespace App\Controller\Api;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/search')]
class SearchController extends AbstractController
{
    #[Route('', name: 'api_search', methods: ['GET'])]
    public function search(Request $request, ArticleRepository $articleRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return $this->json([], Response::HTTP_OK);
        }

        $articles = $articleRepository->searchByTitle($query);

        $results = array_map(function ($article) {
            return [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'url' => '/articles/' . $article->getId(),
                'excerpt' => substr(strip_tags($article->getContent()), 0, 100) . '...'
            ];
        }, $articles);

        return $this->json($results);
    }
}