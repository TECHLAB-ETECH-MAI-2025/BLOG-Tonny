<?php
namespace App\Controller;

use App\Entity\User;
use App\Form\UserForm;
use App\Repository\UserRepository; // Added for index action
use App\Service\UserService; // Added UserService
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/user')]
class UserController extends AbstractController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    #[Route('/', name: 'admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response // Injected UserRepository
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(), // Use UserRepository
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response // Removed EM and Hasher
    {
        $user = new User();
        $form = $this->createForm(UserForm::class, $user, [
            'password_required' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Call UserService to handle creation
            $this->userService->createUserFromForm(
                $user,
                $form->get('plainPassword')->getData(),
                $form->get('isAdmin')->getData()
            );

            $this->addFlash('success', 'Utilisateur créé');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('user/form.html.twig', [
            'form' => $form->createView(),
            'action' => 'create',
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request): Response // Removed EM and Hasher
    {
        $form = $this->createForm(UserForm::class, $user, [
            'disable_username' => true,
            'disable_mail' => true,
            'password_required' => false,
            'current_roles' => $user->getRoles(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Call UserService to handle update
            $this->userService->updateUserFromForm(
                $user,
                $form->get('plainPassword')->getData(),
                $form->get('isAdmin')->getData()
            );

            $this->addFlash('success', 'Utilisateur mis à jour');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('user/form.html.twig', [
            'form' => $form->createView(),
            'action' => 'edit',
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(User $user, Request $request): Response // Removed EM
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            // Call UserService to handle deletion
            $this->userService->deleteUserFromWeb($user);
            $this->addFlash('success', 'Utilisateur supprimé');
        }

        return $this->redirectToRoute('admin_user_index');
    }
}