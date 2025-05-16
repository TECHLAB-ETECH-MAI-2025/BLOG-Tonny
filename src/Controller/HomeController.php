<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ArticleRepository $articleRepository, CategoryRepository $categoryRepository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 2;
        $articles = $articleRepository->paginateArticlesDesc($page, $limit);
        $maxPage = ceil($articles->count() / $limit);

        return $this->render('home/index.html.twig', [
            'articles' => $articles,
            'categories' => $categoryRepository->findAll(),
            'page' => $page,
            'maxPage' => $maxPage,
        ]);
    }

    /*Create once a super Admin*/
    #[Route('/create-my-blog-admin', name: 'create_my_blog_admin_url')]
    public function createAdmin(EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $existingAdmin = $em->getRepository(User::class)->findOneBy(['email' => 'admin@admin.test']);

        if ($existingAdmin) {
            return new Response('Un administrateur existe déjà.');
        }

        $user = new User();
        $user->setEmail('admin@admin.test')
             ->setUsername('admin')
             ->setPassword($hasher->hashPassword($user, '0000'))
             ->setRoles(['ROLE_ADMIN']);

        $em->persist($user);
        $em->flush();

        return new Response('Administrateur créé avec succès!');
    }

    /*Create test data: categories, articles and a test user*/
    #[Route('/create-test-data', name: 'create_test_data')]
    public function createTestData(EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => 'user@blog.test']);

        if (!$existingUser) {
            $user = new User();
            $user->setEmail('user@blog.test')
                 ->setUsername('testuser')
                 ->setPassword($hasher->hashPassword($user, '1234'))
                 ->setRoles(['ROLE_USER']);

            $em->persist($user);
            $resultUser = 'Utilisateur de test créé. ';
        } else {
            $resultUser = 'Utilisateur de test existe déjà. ';
        }

        $categoryNames = [
            'Technologie' => 'Articles sur les nouvelles technologies',
            'Voyage' => 'Découvrez des destinations extraordinaires',
            'Cuisine' => 'Recettes et astuces culinaires',
            'Sport' => 'Actualités sportives et conseils d\'entraînement',
            'Santé' => 'Conseils pour une vie saine',
            'Culture' => 'Art, musique, cinéma et littérature',
            'Science' => 'Découvertes scientifiques et innovations',
            'Finance' => 'Conseils financiers et économiques',
            'Mode' => 'Tendances et conseils mode',
            'Écologie' => 'Environnement et développement durable'
        ];

        $categories = [];
        $categoriesCreated = 0;

        foreach ($categoryNames as $name => $description) {
            $existingCategory = $em->getRepository(Category::class)->findOneBy(['name' => $name]);

            if (!$existingCategory) {
                $category = new Category();
                $category->setName($name)
                         ->setDescription($description);

                $em->persist($category);
                $categories[] = $category;
                $categoriesCreated++;
            } else {
                $categories[] = $existingCategory;
            }
        }

        $articleContents = [
            'Les avancées de l\'IA en 2025' => 'L\'intelligence artificielle continue de progresser à un rythme impressionnant. Dans cet article, nous explorons les dernières innovations dans le domaine de l\'IA et comment elles transforment notre quotidien. Des assistants virtuels plus intelligents aux systèmes de prédiction avancés, découvrez comment cette technologie révolutionne divers secteurs d\'activité.',
            'Guide des destinations incontournables' => 'Vous planifiez vos prochaines vacances? Voici une sélection des destinations les plus prisées cette année. Entre plages paradisiaques, montagnes majestueuses et villes culturelles, il y en a pour tous les goûts. Nous vous proposons également des conseils pratiques pour organiser votre voyage sereinement.',
            'Recettes de saison pour l\'été' => 'Avec l\'arrivée des beaux jours, profitez des produits de saison pour préparer des plats frais et savoureux. Des salades composées aux desserts fruités, découvrez nos meilleures recettes estivales. Simples à réaliser et pleines de saveurs, elles raviront vos papilles et celles de vos invités.',
            'Préparation pour un marathon' => 'Se préparer pour un marathon demande rigueur et persévérance. Dans cet article, nous vous proposons un programme d\'entraînement complet pour atteindre votre objectif. Découvrez également nos conseils sur l\'alimentation, la récupération et l\'équipement nécessaire pour réussir votre défi sportif.',
            'Méditation et bien-être mental' => 'La méditation est une pratique ancestrale aux nombreux bienfaits pour la santé mentale. Apprenez les techniques de base pour débuter et intégrer cette pratique dans votre quotidien. Nous abordons également les différentes formes de méditation et leurs effets spécifiques sur le stress, l\'anxiété et la concentration.',
            'Les expositions artistiques à ne pas manquer' => 'Cette saison culturelle s\'annonce riche en découvertes artistiques. Voici notre sélection des expositions les plus remarquables dans les musées et galeries d\'art. Des grands maîtres classiques aux artistes contemporains émergents, ces événements promettent des moments d\'émerveillement et de réflexion.',
            'Dernières découvertes en astrophysique' => 'Le domaine de l\'astrophysique connaît des avancées fascinantes ces dernières années. Cet article vous présente les découvertes récentes qui bouleversent notre compréhension de l\'univers. Des exoplanètes aux trous noirs, en passant par la matière noire, plongez dans les mystères du cosmos.',
            'Investir intelligemment en période d\'inflation' => 'Face à l\'inflation, il est crucial d\'adopter des stratégies d\'investissement adaptées. Découvrez nos conseils pour protéger et faire fructifier votre patrimoine dans ce contexte économique particulier. Nous analysons différentes options d\'investissement et leurs avantages respectifs.',
            'Les tendances mode de l\'automne' => 'Avec l\'arrivée de l\'automne, les nouvelles collections s\'invitent dans nos garde-robes. Découvrez les tendances incontournables de la saison et comment les adopter selon votre style personnel. Couleurs, matières, coupes... tout ce qu\'il faut savoir pour être à la pointe de la mode.',
            'Initiatives écologiques inspirantes' => 'De plus en plus de projets écologiques voient le jour à travers le monde. Cet article met en lumière des initiatives innovantes qui contribuent à la préservation de notre planète. Sources d\'inspiration, ces projets démontrent qu\'il est possible d\'agir concrètement pour l\'environnement.',
            'Les bienfaits du sommeil sur la santé' => 'Un sommeil de qualité est essentiel pour notre bien-être physique et mental. Découvrez les mécanismes du sommeil et comment optimiser vos nuits pour améliorer votre santé globale. Nous partageons également des astuces pour lutter contre l\'insomnie et les troubles du sommeil.',
            'Les secrets de la cuisine méditerranéenne' => 'Reconnue pour ses bienfaits sur la santé, la cuisine méditerranéenne séduit par sa simplicité et ses saveurs. Découvrez les ingrédients phares de ce régime alimentaire et des recettes authentiques pour voyager à travers vos papilles. Huile d\'olive, légumes frais, poissons... tous les secrets d\'une alimentation équilibrée et gourmande.',
            'Intelligence artificielle et éthique' => 'Le développement rapide de l\'intelligence artificielle soulève d\'importantes questions éthiques. Cet article examine les enjeux liés à l\'utilisation de l\'IA dans notre société et les mesures prises pour encadrer cette technologie. Nous abordons également les débats actuels sur la responsabilité et la transparence des systèmes d\'IA.',
            'Les meilleurs itinéraires de randonnée' => 'Amateurs de nature et d\'aventure, cet article est fait pour vous ! Nous avons sélectionné les plus beaux sentiers de randonnée, des parcours accessibles aux débutants aux trails plus techniques pour les randonneurs expérimentés. Chaque itinéraire est accompagné de conseils pratiques et de points d\'intérêt à ne pas manquer.',
            'Evolution du marché immobilier' => 'Le marché immobilier connaît actuellement d\'importantes mutations. Analysez avec nous les tendances actuelles, l\'évolution des prix et les perspectives d\'avenir dans ce secteur. Que vous soyez acheteur, vendeur ou investisseur, ces informations vous aideront à prendre des décisions éclairées.'
        ];

        $articlesCreated = 0;

        foreach ($articleContents as $title => $content) {
            $existingArticle = $em->getRepository(Article::class)->findOneBy(['title' => $title]);

            if (!$existingArticle) {
                $article = new Article();
                $article->setTitle($title)
                       ->setContent($content);

                //
                $numCategories = rand(1, 3);
                $shuffledCategories = $categories;
                shuffle($shuffledCategories);

                for ($i = 0; $i < $numCategories && $i < count($shuffledCategories); $i++) {
                    $article->addCategory($shuffledCategories[$i]);
                }

                $em->persist($article);
                $articlesCreated++;
            }
        }

        $em->flush();

        return new Response(
            $resultUser .
            $categoriesCreated . ' catégories créées. ' .
            $articlesCreated . ' articles créés.'
        );
    }
}
