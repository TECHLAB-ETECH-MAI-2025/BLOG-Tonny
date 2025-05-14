<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ArticleRepository $articleRepository, CategoryRepository $categoryRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'articles' => $articleRepository->findBy([], ['createdAt' => 'DESC'], 5),
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/create-my-blog-admin', name: 'create_my_blog_admin_url')]
    public function createAdmin(EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();
        $user->setEmail('admin@admin.test')
             ->setUsername('admin')
             ->setPassword($hasher->hashPassword($user, '0000'))
             ->setRoles(['ROLE_ADMIN']);

        $em->persist($user);
        $em->flush();

        return new Response('Administrateur créé avec succès!');
    }
}
