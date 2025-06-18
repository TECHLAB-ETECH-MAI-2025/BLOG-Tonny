<?php
namespace App\Controller\Api;

use App\Entity\Article;
use App\Service\ArticleService;
// use Doctrine\ORM\EntityManagerInterface; // Removed
use InvalidArgumentException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Exception\ValidationFailedException; // This is for the exception class, not the service interface
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
// use Symfony\Component\Validator\Validator\ValidatorInterface; // Removed

#[Route('/api/articles')]
final class ArticleController extends AbstractController
{
    private ArticleService $articleService;
    private SerializerInterface $serializer;
    // private ValidatorInterface $validator; // Removed
    // private EntityManagerInterface $em; // Removed

    public function __construct(
        ArticleService $articleService,
        SerializerInterface $serializer
        // ValidatorInterface $validator, // Removed
        // EntityManagerInterface $em // Removed
    ) {
        $this->articleService = $articleService;
        $this->serializer = $serializer;
        // $this->validator = $validator; // Removed
        // $this->em = $em; // Removed
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

        if (null === $data) {
            return $this->json([
                'error' => 'Invalid JSON data',
                'code' => 'INVALID_JSON'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $article = $this->articleService->createArticleFromApi($data);
            // Using serializer here to ensure consistent output with groups
            $jsonArticle = $this->serializer->normalize($article, null, ['groups' => ['article:read', 'category:list']]);
            return $this->json($jsonArticle, Response::HTTP_CREATED);
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'code' => 'MISSING_REQUIRED_FIELDS' // Or a more generic 'INVALID_INPUT'
            ], Response::HTTP_BAD_REQUEST);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = [
                    'property' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage()
                ];
            }
            return $this->json([
                'error' => 'Validation failed',
                'code' => 'VALIDATION_ERROR',
                'details' => $errorMessages
            ], Response::HTTP_UNPROCESSABLE_ENTITY); // HTTP 422 for validation errors
        } catch (\Exception $e) {
            // Generic error for other unexpected issues
            return $this->json([
                'error' => 'An unexpected error occurred',
                'code' => 'INTERNAL_SERVER_ERROR',
                'details' => $e->getMessage() // Only in dev environment for security
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
        // The check for $article->isDeleted() is now inside the service method,
        // but we can keep a preliminary check here if desired, or rely on the service.
        // For this refactor, we'll rely on the service to throw the LogicException.

        $data = json_decode($request->getContent(), true);

        if (null === $data) {
            return $this->json([
                'error' => 'Invalid JSON data',
                'code' => 'INVALID_JSON'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $updatedArticle = $this->articleService->updateArticleFromApi($article, $data);
            // Using serializer here to ensure consistent output with groups
            $jsonArticle = $this->serializer->normalize($updatedArticle, null, ['groups' => ['article:read', 'category:list']]);
            return $this->json($jsonArticle, Response::HTTP_OK);
        } catch (LogicException $e) { // Catches "Cannot update a soft-deleted article"
            return $this->json([
                'error' => $e->getMessage(),
                'code' => 'UPDATE_DELETED_FORBIDDEN'
            ], Response::HTTP_FORBIDDEN); // Or HTTP_BAD_REQUEST (400)
        } catch (InvalidArgumentException $e) { // Should not happen if title/content are optional in update
            return $this->json([
                'error' => $e->getMessage(),
                'code' => 'INVALID_INPUT'
            ], Response::HTTP_BAD_REQUEST);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = [
                    'property' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage()
                ];
            }
            return $this->json([
                'error' => 'Validation failed',
                'code' => 'VALIDATION_ERROR',
                'details' => $errorMessages
            ], Response::HTTP_UNPROCESSABLE_ENTITY); // HTTP 422 for validation errors
        } catch (\Exception $e) {
            // Generic error for other unexpected issues
            return $this->json([
                'error' => 'An unexpected error occurred',
                'code' => 'INTERNAL_SERVER_ERROR',
                'details' => $e->getMessage() // Only in dev environment for security
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Deletes an article (soft delete).
     */
    #[Route('/{id}', name: 'api_article_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Article $article): JsonResponse
    {
        // This check can remain as it's a quick exit before calling service
        if ($article->isDeleted()) {
            return $this->json(['error' => 'Article déjà supprimé'], Response::HTTP_BAD_REQUEST);
        }

        $this->articleService->deleteArticle($article);
        return $this->json(['message' => 'Article supprimé avec succès'], Response::HTTP_OK);
    }
}