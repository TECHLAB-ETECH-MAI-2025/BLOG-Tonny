<?php
namespace App\Controller\Api;

use App\Entity\Article;
use App\Entity\Category;
use App\Repository\ArticleRepository;
use App\Service\ArticleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/articles')]
final class ArticleController extends AbstractController
{
    private ArticleService $articleService;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private EntityManagerInterface $em;

    public function __construct(
        ArticleService $articleService,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $em
    ) {
        $this->articleService = $articleService;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->em = $em;
    }

    /**
     * Retrieves a paginated list of active articles.
     */
    #[Route('', name: 'api_article_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $paginationData = $this->articleService->getPaginatedArticles($page);

        $articles = [];
        $user = $this->getUser();

        foreach ($paginationData['articles'] as $article) {
            $articleData = $this->serializer->normalize($article, null, ['groups' => ['article:list']]);
            $articleData['isLiked'] = $user ? $article->isLikedBy($user) : false;
            $articleData['likesCount'] = $article->getLikes()->count();
            $articles[] = $articleData;
        }

        return $this->json([
            'data' => $articles,
            'meta' => [
                'pagination' => [
                    'total' => $paginationData['articles']->count(),
                    'page' => $paginationData['page'],
                    'maxPage' => $paginationData['maxPage'],
                    'limit' => 10,
                ]
            ]
        ], Response::HTTP_OK);
    }


    /**
     * Creates a new article.
     */
    #[Route('', name: 'api_article_create', methods: ['POST'])]
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

        // Vérifications requises
        if (!isset($data['title']) || !isset($data['content'])) {
            return $this->json([
                'error' => 'Titre et contenu sont requis',
                'code' => 'MISSING_REQUIRED_FIELDS'
            ], Response::HTTP_BAD_REQUEST);
        }

        $article = new Article();
        $article->setTitle($data['title']);
        $article->setContent($data['content']);

        // AJOUT : Gestion des catégories lors de la création
        if (isset($data['categories']) && is_array($data['categories'])) {
            $categoryRepository = $this->em->getRepository(Category::class);
            foreach ($data['categories'] as $categoryId) {
                $category = $categoryRepository->find($categoryId);
                if ($category) {
                    $article->addCategory($category);
                }
            }
        }

        $errors = $this->validator->validate($article);
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

        $this->em->persist($article);
        $this->em->flush();

        return $this->json($article, Response::HTTP_CREATED, [], ['groups' => ['article:read', 'category:list']]);
    }

    /**
     * Retrieves details of a specific active article.
     */
    #[Route('/{id}', name: 'api_article_show', methods: ['GET'])]
    public function show(Article $article, Request $request): JsonResponse
    {
        if ($article->isDeleted()) {
            return $this->json(['error' => 'Article non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $includeFullDetails = $request->query->getBoolean('full', false);

        $groups = $includeFullDetails
            ? ['article:read', 'article:list', 'article:read:full']
            : ['article:read', 'article:list','category:list'];

        return $this->json($article, Response::HTTP_OK, [], ['groups' => $groups]);
    }


    /**
     * Updates an existing article.
     */
    #[Route('/{id}', name: 'api_article_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Request $request, Article $article): JsonResponse
    {
        if ($article->isDeleted()) {
            return $this->json(['error' => 'Impossible de modifier un article supprimé'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'error' => 'Données JSON invalides',
                'code' => 'INVALID_JSON'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Mise à jour des champs
        if (isset($data['title'])) {
            $article->setTitle($data['title']);
        }
        if (isset($data['content'])) {
            $article->setContent($data['content']);
        }

        // AJOUT : Gestion des catégories
        if (isset($data['categories']) && is_array($data['categories'])) {
            $article->getCategories()->clear();

            // Ajouter les nouvelles catégories
            $categoryRepository = $this->em->getRepository(Category::class);
            foreach ($data['categories'] as $categoryId) {
                $category = $categoryRepository->find($categoryId);
                if ($category) {
                    $article->addCategory($category);
                }
            }
        }

        $errors = $this->validator->validate($article);
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

        $this->em->flush();

        return $this->json($article, Response::HTTP_OK, [], ['groups' => ['article:read', 'category:list']]);
    }

    /**
     * Deletes an article (soft delete).
     */
    #[Route('/{id}', name: 'api_article_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Article $article): JsonResponse
    {
        if ($article->isDeleted()) {
            return $this->json(['error' => 'Article déjà supprimé'], Response::HTTP_BAD_REQUEST);
        }

        $this->articleService->deleteArticle($article);
        return $this->json(['message' => 'Article supprimé avec succès'], Response::HTTP_OK);
    }
}