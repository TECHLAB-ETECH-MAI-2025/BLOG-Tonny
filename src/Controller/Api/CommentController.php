<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\Article;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/comments')]
class CommentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('/article/{id}', name: 'api_comment_index', methods: ['GET'])]
    public function index(Article $article, CommentRepository $commentRepository): JsonResponse
    {
        $comments = $commentRepository->findBy(
            ['article' => $article],
            ['createdAt' => 'DESC']
        );

        return $this->json(
            $comments,
            Response::HTTP_OK,
            [],
            ['groups' => ['comment:read']]
        );
    }

    #[Route('', name: 'api_comment_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'error' => 'Invalid JSON data',
                'code' => 'INVALID_JSON'
            ], Response::HTTP_BAD_REQUEST);
        }

        $comment = new Comment();
        $comment->setAuthor($data['author'] ?? '');
        $comment->setContent($data['content'] ?? '');

        // Associer l'article si l'ID est fourni
        if (isset($data['articleId'])) {
            $article = $this->em->getRepository(Article::class)->find($data['articleId']);
            if ($article) {
                $comment->setArticle($article);
            }
        }

        // Validation
        $errors = $this->validator->validate($comment);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json([
                'error' => 'Validation failed',
                'details' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($comment);
        $this->em->flush();

        return $this->json(
            $comment,
            Response::HTTP_CREATED,
            [],
            ['groups' => ['comment:read']]
        );
    }

    #[Route('/{id}', name: 'api_comment_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Comment $comment): JsonResponse
    {
        $this->em->remove($comment);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}