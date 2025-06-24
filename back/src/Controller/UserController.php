<?php
namespace App\Controller;

use App\Entity\User;
use App\Form\UserForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'admin_user_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $em->getRepository(User::class)->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserForm::class, $user, [
            'password_required' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $hasher->hashPassword($user, $form->get('plainPassword')->getData())
            );

            if ($form->get('isAdmin')->getData()) {
                $user->setRoles(['ROLE_ADMIN']);
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Utilisateur créé');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('user/form.html.twig', [
            'form' => $form->createView(),
            'action' => 'create',
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(UserForm::class, $user, [
            'disable_username' => true,
            'disable_mail' => true,
            'password_required' => false,
            'current_roles' => $user->getRoles(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('plainPassword')->getData()) {
                $user->setPassword(
                    $hasher->hashPassword($user, $form->get('plainPassword')->getData())
                );
            }

            $roles = $form->get('isAdmin')->getData() ? ['ROLE_ADMIN'] : ['ROLE_USER'];
            $user->setRoles($roles);

            $em->flush();

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
    public function delete(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé');
        }

        return $this->redirectToRoute('admin_user_index');
    }
}