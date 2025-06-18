<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedHttpException;
use Symfony\Component\Validator\Constraints as Assert;


class UserService
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;
    private UserRepository $userRepository;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        ValidatorInterface $validator
    ) {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
        $this->userRepository = $userRepository;
        $this->validator = $validator;
    }

    public function createUser(array $data): \App\Entity\User
    {
        // Validate required fields
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            throw new BadRequestHttpException('Username, email, and password are required.');
        }

        // Validate password strength
        try {
            UserRepository::validateStrictPassword($data['password']);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        // Validate email format
        $emailErrors = $this->validator->validate($data['email'], new Assert\Email());
        if (count($emailErrors) > 0) {
            throw new BadRequestHttpException('Invalid email format.');
        }

        // Check email and username uniqueness
        if ($this->userRepository->findOneBy(['email' => $data['email']])) {
            throw new ConflictHttpException('Email already exists.');
        }
        if ($this->userRepository->findOneBy(['username' => $data['username']])) {
            throw new ConflictHttpException('Username already exists.');
        }

        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));

        // Default roles, or allow roles to be passed in $data if appropriate for your application
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);

        // Validate entity
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            // Construct a meaningful error message from violations
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            throw new BadRequestHttpException(implode(', ', $errorMessages));
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function updateUser(\App\Entity\User $user, array $data, \App\Entity\User $currentUser): \App\Entity\User
    {
        // Authorize (admin or self)
        $isSelf = $user->getId() === $currentUser->getId();
        $isAdmin = in_array('ROLE_ADMIN', $currentUser->getRoles());

        if (!$isSelf && !$isAdmin) {
            throw new AccessDeniedHttpException('You are not allowed to update this user.');
        }

        // Update username (check uniqueness if changed)
        if (isset($data['username']) && $user->getUsername() !== $data['username']) {
            if ($this->userRepository->findOneBy(['username' => $data['username']])) {
                throw new ConflictHttpException('Username already exists.');
            }
            $user->setUsername($data['username']);
        }

        // Update email (check format and uniqueness if changed)
        if (isset($data['email']) && $user->getEmail() !== $data['email']) {
            $emailErrors = $this->validator->validate($data['email'], new Assert\Email());
            if (count($emailErrors) > 0) {
                throw new BadRequestHttpException('Invalid email format.');
            }
            if ($this->userRepository->findOneBy(['email' => $data['email']])) {
                throw new ConflictHttpException('Email already exists.');
            }
            $user->setEmail($data['email']);
        }

        // Update password (check strength if changed)
        if (!empty($data['password'])) {
            try {
                UserRepository::validateStrictPassword($data['password']);
            } catch (\InvalidArgumentException $e) {
                throw new BadRequestHttpException($e->getMessage());
            }
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        }

        // Update roles (if admin and 'isAdmin' is set, or more generally 'roles')
        // This logic assumes 'isAdmin' boolean translates to ROLE_ADMIN presence.
        // A more robust system would handle an array of roles directly from $data['roles'].
        if ($isAdmin && isset($data['isAdmin'])) {
            $roles = $user->getRoles();
            if ($data['isAdmin'] && !in_array('ROLE_ADMIN', $roles)) {
                $roles[] = 'ROLE_ADMIN';
            } elseif (!$data['isAdmin'] && in_array('ROLE_ADMIN', $roles)) {
                $roles = array_filter($roles, fn($role) => $role !== 'ROLE_ADMIN');
                // Ensure user always has at least ROLE_USER
                if (!in_array('ROLE_USER', $roles)) {
                    $roles[] = 'ROLE_USER';
                }
            }
            $user->setRoles(array_unique($roles));
        } else if ($isAdmin && isset($data['roles']) && is_array($data['roles'])) {
            // More direct role management if $data['roles'] is provided
             $user->setRoles($data['roles']);
        }


        // Validate entity
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            throw new BadRequestHttpException(implode(', ', $errorMessages));
        }

        $this->em->flush();

        return $user;
    }

    public function createUserFromForm(\App\Entity\User $user, ?string $plainPassword, bool $isAdmin): void
    {
        if ($plainPassword !== null && $plainPassword !== '') {
             try {
                UserRepository::validateStrictPassword($plainPassword);
            } catch (\InvalidArgumentException $e) {
                // This might be better handled by form validation itself,
                // but re-throwing for consistency or logging.
                throw new BadRequestHttpException($e->getMessage());
            }
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        }

        $roles = ['ROLE_USER'];
        if ($isAdmin) {
            $roles[] = 'ROLE_ADMIN';
        }
        $user->setRoles(array_unique($roles));

        // Additional validation could be here if $user is not already validated by the form
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            throw new BadRequestHttpException(implode(', ', $errorMessages));
        }

        $this->em->persist($user);
        $this->em->flush();
    }

    public function updateUserFromForm(\App\Entity\User $user, ?string $plainPassword, bool $isAdmin): void
    {
        if ($plainPassword !== null && $plainPassword !== '') {
            try {
                UserRepository::validateStrictPassword($plainPassword);
            } catch (\InvalidArgumentException $e) {
                throw new BadRequestHttpException($e->getMessage());
            }
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        }

        $roles = $user->getRoles(); // Get existing roles
        if (!in_array('ROLE_USER', $roles)) { // Ensure ROLE_USER is always present
             $roles[] = 'ROLE_USER';
        }

        if ($isAdmin) {
            if (!in_array('ROLE_ADMIN', $roles)) {
                $roles[] = 'ROLE_ADMIN';
            }
        } else {
            // If not admin, remove ROLE_ADMIN if it exists
            $roles = array_filter($roles, fn($role) => $role !== 'ROLE_ADMIN');
        }
        $user->setRoles(array_unique($roles));

        // Additional validation could be here
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            throw new BadRequestHttpException(implode(', ', $errorMessages));
        }

        $this->em->flush();
    }

    public function deleteUser(\App\Entity\User $user, \App\Entity\User $currentUser): void
    {
        // Authorize (admin only, no self-delete this way)
        if (!in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            throw new AccessDeniedHttpException('You are not allowed to delete this user.');
        }
        if ($user->getId() === $currentUser->getId()) {
            throw new AccessDeniedHttpException('Administrators cannot delete their own account through this method.');
        }
        // Prevent deleting super admin if that's a concept in your app
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles())) {
             throw new AccessDeniedHttpException('You are not allowed to delete a super administrator.');
        }


        $this->em->remove($user);
        $this->em->flush();
    }

    public function deleteUserFromWeb(\App\Entity\User $user): void
    {
        // This method implies less stringent checks or is called from a context
        // where authorization has already been performed (e.g., Admin CRUD controller for users).
        // If this is for a user deleting their own account, additional checks (e.g. password confirmation)
        // might be needed, or that would be a different method.
        $this->em->remove($user);
        $this->em->flush();
    }
}
