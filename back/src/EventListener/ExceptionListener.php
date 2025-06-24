<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ExceptionListener
{
    private UrlGeneratorInterface $urlGenerator;
    private RequestStack $requestStack;
    private Environment $twig;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack,
        Environment $twig
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
        $this->twig = $twig;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Gestion des erreurs d'accès refusé (403)
        if ($exception instanceof AccessDeniedException || $exception instanceof AccessDeniedHttpException) {
            $content = $this->twig->render('error/access_denied.html.twig', [
                'exception' => $exception,
                'status_code' => Response::HTTP_FORBIDDEN,
                'status_text' => 'Accès refusé'
            ]);

            $response = new Response($content, Response::HTTP_FORBIDDEN);
            $event->setResponse($response);
            return;
        }

        // Gestion des erreurs 404 Not Found
        if ($exception instanceof NotFoundHttpException) {
            $content = $this->twig->render('error/not_found.html.twig', [
                'exception' => $exception,
                'status_code' => Response::HTTP_NOT_FOUND,
                'status_text' => 'Page non trouvée'
            ]);

            $response = new Response($content, Response::HTTP_NOT_FOUND);
            $event->setResponse($response);
            return;
        }

    }
}