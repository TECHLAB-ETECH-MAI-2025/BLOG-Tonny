<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Service\APITestService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TestController extends AbstractController
{

    public function __construct(private readonly APITestService $apiTestService)
    {

    }

    #[Route('/test', name: 'app_test')]
    public function index(): Response
    {
        return $this->render('test/index.html.twig', [
            'controller_name' => 'TestController',
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/getApiTest', name: 'app_get_api', methods: ['GET'])]
    public function getApiTest(HttpClientInterface $httpClient): JsonResponse
    {
        $response = $httpClient->request('GET', 'https://jsonplaceholder.typicode.com/posts');
        $content = $response->toArray();
        $data = [
            'status' => 200,
            'data' => $content
        ];
        return $this->json($data);
    }


    /**
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/conversion', name: 'app_conversion', methods: ['GET', 'POST'])]
    public function convertCurrency(Request $request): JsonResponse
    {
        $content = $this->apiTestService->getConversion($request);
        return $this->json($content);
    }
}
