<?php

namespace App\Controller\Api;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    /**
     * @throws RandomException
     */
    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['username']) || !isset($data['password'])) {
            return $this->json([
                'error' => 'Identifiants manquants',
                'code' => 'MISSING_CREDENTIALS'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Trouver l'utilisateur par username ou email
        $user = $em->getRepository(User::class)->findOneBy(['username' => $data['username']]);
        if (!$user) {
            $user = $em->getRepository(User::class)->findOneBy(['email' => $data['username']]);
        }

        if (!$user || !$hasher->isPasswordValid($user, $data['password'])) {
            return $this->json([
                'error' => 'Identifiants invalides',
                'code' => 'INVALID_CREDENTIALS'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Mettre à jour la dernière activité
        $user->setLastActivity(new \DateTimeImmutable());

        $deviceName = $data['device_name'] ?? 'Token API';
        $apiToken = new ApiToken($user, $deviceName);

        $em->persist($apiToken);
        $em->flush();

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'isOnline' => $user->isOnline(),
                'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ],
            'token' => $apiToken->getToken(),
            'expires_at' => $apiToken->getExpiresAt()->format(\DateTimeInterface::ATOM),
        ]);
    }

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'error' => 'Données JSON invalides',
                'code' => 'INVALID_JSON'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validation des champs requis
        $requiredFields = ['username', 'email', 'password'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->json([
                    'error' => "Le champ '$field' est requis",
                    'code' => 'MISSING_REQUIRED_FIELD'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Validation stricte du mot de passe
        $passwordError = UserRepository::validateStrictPassword($data['password']);
        if ($passwordError) {
            return $this->json($passwordError, Response::HTTP_BAD_REQUEST);
        }

        // Validation de l'email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'error' => 'Format d\'email invalide',
                'code' => 'INVALID_EMAIL_FORMAT'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json([
                'error' => 'Un utilisateur avec cet email existe déjà',
                'code' => 'USER_ALREADY_EXISTS'
            ], Response::HTTP_CONFLICT);
        }

        $existingUsername = $em->getRepository(User::class)->findOneBy(['username' => $data['username']]);
        if ($existingUsername) {
            return $this->json([
                'error' => 'Ce nom d\'utilisateur est déjà pris',
                'code' => 'USERNAME_TAKEN'
            ], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setPassword($hasher->hashPassword($user, $data['password']));
        $user->setRoles(['ROLE_USER']);

        // Validation de l'entité
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

        return $this->json([
            'message' => 'Utilisateur enregistré avec succès',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'error' => 'Non authentifié',
                'code' => 'NOT_AUTHENTICATED'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'isOnline' => $user->isOnline(),
                'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'lastActivity' => $user->getLastActivity()?->format(\DateTimeInterface::ATOM),
            ]
        ]);
    }

    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(
        #[CurrentUser] ?User $user,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$user) {
            return $this->json([
                'error' => 'Non authentifié',
                'code' => 'NOT_AUTHENTICATED'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $authHeader = $request->headers->get('Authorization', '');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->json([
                'error' => 'Aucun token fourni',
                'code' => 'NO_TOKEN_PROVIDED'
            ], Response::HTTP_BAD_REQUEST);
        }

        $token = substr($authHeader, 7);

        $apiToken = $em->getRepository(ApiToken::class)->findOneBy([
            'token' => $token,
            'user' => $user
        ]);

        if (!$apiToken) {
            return $this->json([
                'error' => 'Token invalide',
                'code' => 'INVALID_TOKEN'
            ], Response::HTTP_BAD_REQUEST);
        }

        $em->remove($apiToken);
        $em->flush();

        return $this->json([
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * Validation stricte du mot de passe
     */

}