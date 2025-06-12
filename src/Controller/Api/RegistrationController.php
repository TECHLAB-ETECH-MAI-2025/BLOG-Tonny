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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth', name: 'api_')]
class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse([
                'error' => 'Données JSON invalides',
                'code' => 'INVALID_JSON'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validation des champs requis
        $requiredFields = ['username', 'email', 'password'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return new JsonResponse([
                    'error' => "Le champ '$field' est requis",
                    'code' => 'MISSING_REQUIRED_FIELD'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Validation stricte du mot de passe
        $passwordError = UserRepository::validateStrictPassword($data['password']);
        if ($passwordError) {
            return new JsonResponse($passwordError, Response::HTTP_BAD_REQUEST);
        }

        // Validation du format email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'error' => 'Format d\'email invalide',
                'code' => 'INVALID_EMAIL_FORMAT'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse([
                'error' => 'Un utilisateur avec cet email existe déjà',
                'code' => 'USER_ALREADY_EXISTS'
            ], Response::HTTP_CONFLICT);
        }

        $existingUsername = $entityManager->getRepository(User::class)->findOneBy(['username' => $data['username']]);
        if ($existingUsername) {
            return new JsonResponse([
                'error' => 'Ce nom d\'utilisateur est déjà pris',
                'code' => 'USERNAME_TAKEN'
            ], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setPassword($userPasswordHasher->hashPassword($user, $data['password']));
        $user->setRoles(['ROLE_USER']);

        // Validation de l'entité
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse([
                'error' => 'Échec de la validation',
                'code' => 'VALIDATION_ERROR',
                'details' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Utilisateur enregistré avec succès',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ]
        ], Response::HTTP_CREATED);
    }


}