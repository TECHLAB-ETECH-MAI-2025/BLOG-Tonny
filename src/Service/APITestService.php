<?php
    namespace App\Service;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class APITestService
{
    public function __construct(private HttpClientInterface $httpClient){}
    /**
     * Convertir une devise à une autre via VISA
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getConversion(Request $request): array
    {
        $amount = $request->query->get('amount',1);
        $fromCurr = $request->query->get('fromCurr','EUR');
        $toCurr = $request->query->get('toCurr','MGA');

        $response = $this->httpClient->request('GET', 'https://www.visa.fr/cmsapi/fx/rates', [
            'query' => [
                'amount' => $amount,
                'fee' => 0,
                'utcConvertedDate' => (new \DateTime())->format('m/d/Y'),
                'exchangedate' => (new \DateTime())->format('m/d/Y'),
                'toCurr' => $toCurr,
                'fromCurr' => $fromCurr
            ]
        ]);

        return $response->toArray();
    }
}