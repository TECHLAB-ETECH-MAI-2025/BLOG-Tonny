<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService; // Added UserService
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedHttpException;

#[Route('/api/users')]
class UserController extends AbstractController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    #[Route('', name: 'api_users_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();

        return $this->json($users, Response::HTTP_OK, [], [
            'groups' => ['user:read']
        ]);
    }

    #[Route('/me', name: 'api_users_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'error' => 'Non authentifié',
                'code' => 'NOT_AUTHENTICATED'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($user, Response::HTTP_OK, [], [
            'groups' => ['user:read']
        ]);
    }

    #[Route('/{id}', name: 'api_users_show', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        return $this->json($user, Response::HTTP_OK, [], [
            'groups' => ['user:read']
        ]);
    }

    #[Route('', name: 'api_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON data', 'code' => 'INVALID_JSON'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->userService->createUser($data);
            return $this->json($user, Response::HTTP_CREATED, [], ['groups' => ['user:read']]);
        } catch (BadRequestHttpException $e) {
            return $this->json(['error' => $e->getMessage(), 'code' => 'BAD_REQUEST'], Response::HTTP_BAD_REQUEST);
        } catch (ConflictHttpException $e) {
            return $this->json(['error' => $e->getMessage(), 'code' => 'CONFLICT'], Response::HTTP_CONFLICT);
        } catch (\Exception $e) {
            // Log the exception details in a real application
            return $this->json(['error' => 'An unexpected error occurred', 'code' => 'INTERNAL_SERVER_ERROR', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_users_update', methods: ['PUT', 'PATCH'])]
    public function update(User $user, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
             return $this->json(['error' => 'Not authenticated or user not found.', 'code' => 'UNAUTHENTICATED'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON data', 'code' => 'INVALID_JSON'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $updatedUser = $this->userService->updateUser($user, $data, $currentUser);
            return $this->json($updatedUser, Response::HTTP_OK, [], ['groups' => ['user:read']]);
        } catch (AccessDeniedHttpException $e) {
            return $this->json(['error' => $e->getMessage(), 'code' => 'ACCESS_DENIED'], Response::HTTP_FORBIDDEN);
        } catch (BadRequestHttpException $e) {
            return $this->json(['error' => $e->getMessage(), 'code' => 'BAD_REQUEST'], Response::HTTP_BAD_REQUEST);
        } catch (ConflictHttpException $e) {
            return $this->json(['error' => $e->getMessage(), 'code' => 'CONFLICT'], Response::HTTP_CONFLICT);
        } catch (\Exception $e) {
            // Log the exception details
            return $this->json(['error' => 'An unexpected error occurred', 'code' => 'INTERNAL_SERVER_ERROR', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function delete(User $user): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
             return $this->json(['error' => 'Not authenticated or user not found.', 'code' => 'UNAUTHENTICATED'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $this->userService->deleteUser($user, $currentUser);
            // The original controller returned HTTP_OK with a message.
            // HTTP_NO_CONTENT (204) is also common for successful DELETE operations.
            // Sticking to original pattern for now.
            return $this->json(['message' => 'User deleted successfully', 'code' => 'USER_DELETED'], Response::HTTP_OK);
        } catch (AccessDeniedHttpException $e) {
            return $this->json(['error' => $e->getMessage(), 'code' => 'ACCESS_DENIED'], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            // Log the exception details
            return $this->json(['error' => 'An unexpected error occurred', 'code' => 'INTERNAL_SERVER_ERROR', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
