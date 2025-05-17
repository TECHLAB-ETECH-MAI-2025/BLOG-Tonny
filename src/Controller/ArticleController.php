<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleForm;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/article')]
final class ArticleController extends AbstractController
{
    /**
     * Affiche une liste paginée des articles.
     *
     * Cette méthode est réservée aux administrateurs (ROLE_ADMIN).
     * Elle récupère la page courante via la requête et utilise le repository pour paginer les articles.
     *
     * @param ArticleRepository $articleRepository Le repository des articles
     * @param Request $request La requête HTTP
     * @return Response La réponse contenant la vue des articles paginés
     */
    #[Route(name: 'app_article_index', methods: ['GET'])]
    #[isGranted('ROLE_ADMIN')]
    public function index(ArticleRepository $articleRepository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $articles = $articleRepository->paginateArticles($page, $limit = 10);
        $maxPage = ceil($articles->count() / $limit);
        return $this->render('article/index.html.twig', [
            'articles' => $articles,
            'maxPage' => $maxPage,
            'page' => $page,
        ]);
    }

    /**
     * Crée un nouvel article.
     *
     * Affiche un formulaire de création et traite sa soumission.
     * Accessible uniquement par un administrateur (ROLE_ADMIN).
     *
     * @param Request $request La requête HTTP
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités Doctrine
     * @return Response La réponse avec le formulaire ou une redirection après création
     */
    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    #[isGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleForm::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash("success", "L'article a bien été ajouté");
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    /**
     * Affiche le détail d'un article.
     *
     * @param Article $article L'entité Article à afficher (paramConverter)
     * @return Response La réponse contenant la vue détaillée de l'article
     */
    #[Route('/{id}', name: 'app_article_show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    /**
     * Modifie un article existant.
     *
     * Affiche et traite le formulaire d'édition pour l'article donné.
     * Accessible uniquement par un administrateur (ROLE_ADMIN).
     *
     * @param Request $request La requête HTTP
     * @param Article $article L'article à modifier (paramConverter)
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités Doctrine
     * @return Response La réponse avec le formulaire ou une redirection après modification
     */
    #[Route('/{id}/edit', name: 'app_article_edit', methods: ['GET', 'POST'])]
    #[isGranted('ROLE_ADMIN')]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ArticleForm::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash("success", "L'article a bien été modifié");
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    /**
     * Supprime un article.
     *
     * Cette action est protégée par un token CSRF et réservée à un administrateur (ROLE_ADMIN).
     *
     * @param Request $request La requête HTTP
     * @param Article $article L'article à supprimer (paramConverter)
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités Doctrine
     * @return Response Redirection vers la liste des articles
     */
    #[Route('/{id}', name: 'app_article_delete', methods: ['POST'])]
    #[isGranted('ROLE_ADMIN')]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($article);
            $entityManager->flush();
            $this->addFlash("success", "L'article a bien été supprimé");
        }

        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
    }
}