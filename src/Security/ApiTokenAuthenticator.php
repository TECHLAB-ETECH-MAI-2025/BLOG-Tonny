<?php

namespace App\Security;

use App\Entity\ApiToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->getPathInfo(), '/api') &&
            $request->headers->has('Authorization') &&
            str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new CustomUserMessageAuthenticationException('Aucun token API fourni');
        }

        $token = substr($authHeader, 7);

        if (empty($token)) {
            throw new CustomUserMessageAuthenticationException('Aucun token API fourni');
        }

        $apiToken = $this->em->getRepository(ApiToken::class)->findOneBy(['token' => $token]);

        if (!$apiToken) {
            throw new CustomUserMessageAuthenticationException('Token API invalide');
        }

        if ($apiToken->isExpired()) {
            // Supprimer le token expiré
            $this->em->remove($apiToken);
            $this->em->flush();
            throw new CustomUserMessageAuthenticationException('Token API expiré');
        }

        // Mettre à jour la dernière utilisation du token et l'activité de l'utilisateur
        $apiToken->setLastUsedAt(new \DateTimeImmutable());
        $apiToken->getUser()->setLastActivity(new \DateTimeImmutable());
        $this->em->flush();

        return new SelfValidatingPassport(
            new UserBadge($apiToken->getUser()->getUserIdentifier())
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // Laisser la requête continuer
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => 'Échec de l\'authentification',
            'code' => 'AUTHENTICATION_FAILED',
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ], Response::HTTP_UNAUTHORIZED);
    }
}