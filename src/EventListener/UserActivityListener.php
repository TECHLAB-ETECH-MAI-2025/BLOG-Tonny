<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Met à jour l'activité de l'utilisateur à chaque requête
 */
class UserActivityListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenStorageInterface  $tokenStorage,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function onKernelController(ControllerEvent $event): void
    {
        // Vérifier si c'est la requête principale
        if (!$event->isMainRequest()) {
            return;
        }

        // Récupérer l'utilisateur connecté
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Mettre à jour la dernière activité
        $user->setLastActivity(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}