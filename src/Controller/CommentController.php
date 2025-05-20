<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Article;
use App\Form\CommentForm;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comment')]
class CommentController extends AbstractController
{
    #[Route('/', name: 'app_comment_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(CommentRepository $commentRepository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $comments = $commentRepository->paginateComments($page, $limit = 2);
        $maxPage = ceil($comments->count() / $limit);
        return $this->render('comment/index.html.twig', [
            'comments' => $comments,
            'maxPage' => $maxPage,
            'page' => $page,
        ]);
    }

    #[Route('/new', name: 'app_comment_new', methods: ['GET', 'POST'])]
    #[isGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentForm::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('comment/new.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/new-for-article/{id}', name: 'app_comment_new_for_article', methods: ['POST'])]
    public function newForArticle(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        if (!$this->isGranted('ROLE_USER')) {
            $response->setContent(json_encode([
                'success' => false,
                'redirect' => $this->generateUrl('app_login')
            ]));
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            return $response;
        }

        $comment = new Comment();

        $data = $request->request->all();
        $commentData = $data['comment'] ?? [];

        if (empty($commentData['author']) || empty($commentData['content'])) {
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'Auteur et contenu sont requis'
            ]));
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            return $response;
        }

        $comment->setAuthor($commentData['author']);
        $comment->setContent($commentData['content']);
        $comment->setArticle($article);

        $entityManager->persist($comment);
        $entityManager->flush();

        $response->setContent(json_encode([
            'success' => true,
            'message' => 'Commentaire ajouté avec succès'
        ]));

        return $response;
    }


    #[Route('/{id}', name: 'app_comment_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(Comment $comment): Response
    {
        return $this->render('comment/show.html.twig', [
            'comment' => $comment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommentForm::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_comment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
    }
}