<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
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
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'error' => 'Données JSON invalides',
                'code' => 'INVALID_JSON'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifications requises
        if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'error' => 'Pseudo, email et mot de passe sont requis',
                'code' => 'MISSING_REQUIRED_FIELDS'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Valider le mot de passe
        $passwordError = UserRepository::validateStrictPassword($data['password']);
        if ($passwordError) {
            return $this->json($passwordError, Response::HTTP_BAD_REQUEST);
        }

        // Vérifier l'unicité de l'email
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json([
                'error' => 'Un utilisateur avec cet email existe déjà',
                'code' => 'EMAIL_ALREADY_EXISTS'
            ], Response::HTTP_CONFLICT);
        }

        // Vérifier l'unicité du nom d'utilisateur
        $existingUsername = $em->getRepository(User::class)->findOneBy(['username' => $data['username']]);
        if ($existingUsername) {
            return $this->json([
                'error' => 'Ce nom d\'utilisateur est déjà pris',
                'code' => 'USERNAME_TAKEN'
            ], Response::HTTP_CONFLICT);
        }

        // Validation de l'email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'error' => 'Format d\'email invalide',
                'code' => 'INVALID_EMAIL_FORMAT'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setPassword($hasher->hashPassword($user, $data['password']));

        // Gérer le rôle admin
        if (isset($data['isAdmin']) && $data['isAdmin']) {
            $user->setRoles(['ROLE_ADMIN']);
        } else {
            $user->setRoles(['ROLE_USER']);
        }

        $errors = $validator->validate($user);
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

        $em->persist($user);
        $em->flush();

        return $this->json($user, Response::HTTP_CREATED, [], [
            'groups' => ['user:read']
        ]);
    }

    #[Route('/{id}', name: 'api_users_update', methods: ['PUT', 'PATCH'])]
    public function update(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $currentUser = $this->getUser();

        if ($currentUser !== $user && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return $this->json([
                'error' => 'Accès refusé',
                'code' => 'ACCESS_DENIED'
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'error' => 'Données JSON invalides',
                'code' => 'INVALID_JSON'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validation et mise à jour du mot de passe si fourni
        if (isset($data['password'])) {
            $passwordError = UserRepository::validateStrictPassword($data['password']);
            if ($passwordError) {
                return $this->json($passwordError, Response::HTTP_BAD_REQUEST);
            }
            $user->setPassword($hasher->hashPassword($user, $data['password']));
        }

        // Validation de l'email si fourni
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'error' => 'Format d\'email invalide',
                'code' => 'INVALID_EMAIL_FORMAT'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier l'unicité de l'email si modifié
        if (isset($data['email']) && $data['email'] !== $user->getEmail()) {
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                return $this->json([
                    'error' => 'Un utilisateur avec cet email existe déjà',
                    'code' => 'EMAIL_ALREADY_EXISTS'
                ], Response::HTTP_CONFLICT);
            }
            $user->setEmail($data['email']);
        }

        // Vérifier l'unicité du nom d'utilisateur si modifié
        if (isset($data['username']) && $data['username'] !== $user->getUsername()) {
            $existingUsername = $em->getRepository(User::class)->findOneBy(['username' => $data['username']]);
            if ($existingUsername) {
                return $this->json([
                    'error' => 'Ce nom d\'utilisateur est déjà pris',
                    'code' => 'USERNAME_TAKEN'
                ], Response::HTTP_CONFLICT);
            }
            $user->setUsername($data['username']);
        }

        // Gérer le rôle admin
        if (isset($data['isAdmin'])) {
            $roles = $data['isAdmin'] ? ['ROLE_ADMIN'] : ['ROLE_USER'];
            $user->setRoles($roles);
        }

        $errors = $validator->validate($user);
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

        $em->flush();

        return $this->json($user, Response::HTTP_OK, [], [
            'groups' => ['user:read']
        ]);
    }

    #[Route('/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function delete(User $user, EntityManagerInterface $em): JsonResponse
    {
        $currentUser = $this->getUser();

        if (!in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return $this->json([
                'error' => 'Accès refusé - Seuls les administrateurs peuvent supprimer des utilisateurs',
                'code' => 'ACCESS_DENIED'
            ], Response::HTTP_FORBIDDEN);
        }

        // Empêcher la suppression de son propre compte
        if ($currentUser === $user) {
            return $this->json([
                'error' => 'Vous ne pouvez pas supprimer votre propre compte',
                'code' => 'CANNOT_DELETE_SELF'
            ], Response::HTTP_FORBIDDEN);
        }

        $em->remove($user);
        $em->flush();

        return $this->json([
            'message' => 'Utilisateur supprimé avec succès',
            'code' => 'USER_DELETED'
        ], Response::HTTP_OK);
    }
}
