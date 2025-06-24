<?php

namespace App\Controller\Api;

use App\Entity\Article;
use App\Entity\Like;
use App\Repository\LikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/api')]
class LikeController extends AbstractController
{
    #[Route('/articles/{id}/like', name: 'api_article_toggle_like', methods: ['POST'])]
    public function toggleLike(
        Article $article,
        Request $request,
        EntityManagerInterface $em,
        LikeRepository $likeRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        $like = $likeRepository->findOneBy(['article' => $article, 'user' => $user]);

        if ($like) {
            // Unlike the article
            $em->remove($like);
            $isLiked = false;
        } else {
            // Like the article
            $like = new Like();
            $like->setArticle($article);
            $like->setUser($user);
            $em->persist($like);
            $isLiked = true;
        }

        $em->flush();

        $likesCount = $likeRepository->count(['article' => $article]);

        return new JsonResponse([
            'success' => true,
            'isLiked' => $isLiked,
            'likesCount' => $likesCount
        ]);
    }
}