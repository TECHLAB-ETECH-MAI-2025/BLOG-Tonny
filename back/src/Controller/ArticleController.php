<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleForm;
use App\Service\ArticleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('admin/article')]
final class ArticleController extends AbstractController
{
    private ArticleService $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    /**
     * Affiche une liste paginée des articles actifs.
     */
    #[Route(name: 'app_article_index', methods: ['GET'])]
    #[isGranted('ROLE_ADMIN')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $paginationData = $this->articleService->getPaginatedArticles($page);

        return $this->render('article/index.html.twig', $paginationData);
    }

    /**
     * Crée un nouvel article.
     */
    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    #[isGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleForm::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->articleService->createArticle($article);
            $this->addFlash("success", "L'article a bien été ajouté");
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    /**
     * Affiche le détail d'un article (actif uniquement).
     */
    #[Route('/{id}', name: 'app_article_show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        // Vérifier si l'article est supprimé
        if ($article->isDeleted()) {
            throw $this->createNotFoundException('Article non trouvé');
        }

        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    /**
     * Modifie un article existant.
     */
    #[Route('/{id}/edit', name: 'app_article_edit', methods: ['GET', 'POST'])]
    #[isGranted('ROLE_ADMIN')]
    public function edit(Request $request, Article $article): Response
    {
        // Empêcher la modification d'articles supprimés
        if ($article->isDeleted()) {
            $this->addFlash("error", "Impossible de modifier un article supprimé");
            return $this->redirectToRoute('app_article_index');
        }

        $form = $this->createForm(ArticleForm::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->articleService->updateArticle($article);
            $this->addFlash("success", "L'article a bien été modifié");
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    /**
     * Supprime un article (soft delete).
     */
    #[Route('/{id}', name: 'app_article_delete', methods: ['POST'])]
    #[isGranted('ROLE_ADMIN')]
    public function delete(Request $request, Article $article): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->getPayload()->getString('_token'))) {
            $this->articleService->deleteArticle($article);
            $this->addFlash("success", "L'article a bien été supprimé");
        }

        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
    }
}