<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Like;
use App\Repository\LikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/like')]
class LikeController extends AbstractController
{
    #[Route('/article/{id}', name: 'app_like_toggle', methods: ['POST'])]
    public function toggleLike(Article $article, Request $request, EntityManagerInterface $em, LikeRepository $likeRepository): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['redirect' => $this->generateUrl('app_login')], 401);
        }
        if (!$this->isCsrfTokenValid('like' . $article->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Token CSRF invalide'], 403);
        }

        $user = $this->getUser();
        $like = $likeRepository->findOneBy(['article' => $article, 'user' => $user]);
        if ($like) {
            $em->remove($like);
            $isLiked = false;
        } else {
            $like = new Like();
            $like->setArticle($article);
            $like->setUser($user);
            $em->persist($like);
            $isLiked = true;
        }
        $em->flush();

        $likesCount = $likeRepository->countLikesForArticle($article);

        return new JsonResponse([
            'isLiked' => $isLiked,
            'likesCount' => $likesCount
        ]);
    }
}