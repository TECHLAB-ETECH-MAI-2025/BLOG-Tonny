<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/categories')]
final class CategoryController extends AbstractController
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    /**
     * Récupère la liste paginée des catégories
     */
    #[Route('', name: 'api_category_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = 10;

        $paginator = $this->categoryRepository->paginateCategories($page, $limit);
        $totalItems = count($paginator);
        $maxPage = ceil($totalItems / $limit);

        $categories = [];
        foreach ($paginator as $category) {
            $categories[] = $category;
        }

        return $this->json([
            'data' => $categories,
            'meta' => [
                'pagination' => [
                    'total' => $totalItems,
                    'page' => $page,
                    'maxPage' => $maxPage,
                    'limit' => $limit,
                ]
            ]
        ], Response::HTTP_OK, [], ['groups' => ['category:list']]);
    }

    /**
     * Crée une nouvelle catégorie
     */
    #[Route('', name: 'api_category_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'error' => 'Données JSON invalides',
                'code' => 'INVALID_JSON'
            ], Response::HTTP_BAD_REQUEST);
        }

        $category = new Category();
        $category->setName($data['name'] ?? '');
        $category->setDescription($data['description'] ?? null);

        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json([
                'error' => 'Échec de la validation',
                'code' => 'VALIDATION_ERROR',
                'details' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $this->json(
            $category,
            Response::HTTP_CREATED,
            ['Location' => $this->generateUrl('api_category_show', ['id' => $category->getId()])],
            ['groups' => ['category:read']]
        );
    }

    /**
     * Récupère les détails d'une catégorie
     */
    #[Route('/{id}', name: 'api_category_show', methods: ['GET'])]
    public function show(Category $category): JsonResponse
    {
        return $this->json($category, Response::HTTP_OK, [], ['groups' => ['category:read']]);
    }

    /**
     * Met à jour une catégorie existante
     */
    #[Route('/{id}', name: 'api_category_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Request $request, Category $category): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'error' => 'Données JSON invalides',
                'code' => 'INVALID_JSON'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $category->setName($data['name']);
        }

        if (array_key_exists('description', $data)) {
            $category->setDescription($data['description']);
        }

        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json([
                'error' => 'Échec de la validation',
                'code' => 'VALIDATION_ERROR',
                'details' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json($category, Response::HTTP_OK, [], ['groups' => ['category:read']]);
    }

    /**
     * Supprime une catégorie
     */
    #[Route('/{id}', name: 'api_category_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Category $category): JsonResponse
    {
        if ($category->getArticles()->count() > 0) {
            return $this->json([
                'error' => 'Impossible de supprimer une catégorie associée à des articles',
                'code' => 'CATEGORY_IN_USE'
            ], Response::HTTP_CONFLICT);
        }

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}