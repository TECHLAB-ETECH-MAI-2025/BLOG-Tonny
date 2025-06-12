<?php

namespace App\Controller\Api;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CsrfTokenController extends AbstractController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/api/csrf-token', name: 'api_csrf_token', methods: ['GET'])]
    public function getCsrfToken(): JsonResponse
    {
        // Generate and return a CSRF token
        $csrfToken = $this->container->get('security.csrf.token_manager')->getToken('your_intention')->getValue();

        return $this->json(['token' => $csrfToken]);
    }
}
