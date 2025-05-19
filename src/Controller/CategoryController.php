<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryForm;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/category')]
#[isGranted('ROLE_ADMIN')]
final class CategoryController extends AbstractController
{
    /**
     * Affiche la liste paginée des catégories.
     *
     * Cette méthode récupère la page courante depuis la requête et utilise le repository
     * pour paginer les catégories. Accessible uniquement aux administrateurs.
     *
     * @param CategoryRepository $categoryRepository
     * @param Request $request
     * @return Response
     */
    #[Route(name: 'app_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $categories = $categoryRepository->paginateCategories($page, $limit = 10);
        $maxPage = ceil($categories->count() / $limit);
        return $this->render('category/index.html.twig', [
            'categories' => $categories,
            'maxPage' => $maxPage,
            'page' => $page,
        ]);
    }

    /**
     * Crée une nouvelle catégorie.
     *
     * Affiche un formulaire de création et traite sa soumission.
     * Accessible uniquement aux administrateurs.
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryForm::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();
            $this->addFlash('success', 'La catégorie a été créée avec succès!');

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    /**
     * Affiche le détail d'une catégorie.
     *
     * @param Category $category
     * @return Response
     */
    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }

    /**
     * Modifie une catégorie existante.
     *
     * Affiche et traite le formulaire d'édition pour la catégorie donnée.
     * Accessible uniquement aux administrateurs.
     *
     * @param Request $request
     * @param Category $category
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryForm::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La catégorie a été modifiée avec succès!');

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    /**
     * Supprime une catégorie.
     *
     * Cette action est protégée par un token CSRF.
     * Accessible uniquement aux administrateurs.
     *
     * @param Request $request
     * @param Category $category
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
            $this->addFlash('success', 'La catégorie a été supprimée avec succès!');
        }

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }
}