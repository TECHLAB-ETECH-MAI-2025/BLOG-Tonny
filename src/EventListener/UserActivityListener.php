<?php
namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Psr\Log\LoggerInterface;

class UserActivityListener implements EventSubscriberInterface
{
    private const UPDATE_INTERVAL = 300; // 5 minutes
    private const CACHE_TTL = 3600; // 1 heure pour éviter l'accumulation

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly EntityManagerInterface $entityManager,
        private readonly CacheInterface $cache,
        private readonly ?LoggerInterface $logger = null
    ) {}

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token || !($user = $token->getUser()) instanceof User) {
            return;
        }

        $userId = $user->getId();
        $cacheKey = "user_activity_{$userId}";

        try {
            // Vérifier si on a déjà mis à jour récemment
            $lastUpdate = $this->cache->get($cacheKey, fn() => 0);

            $now = time();
            if (($now - $lastUpdate) >= self::UPDATE_INTERVAL) {
                // Mettre à jour l'activité
                $user->setLastActivity(new \DateTimeImmutable());
                $this->entityManager->flush();

                // Sauvegarder le timestamp en cache
                $this->cache->delete($cacheKey);
                $this->cache->get($cacheKey, fn() => $now);
            }
        } catch (\Exception $e) {
            // En cas d'erreur de cache, on log mais on n'interrompt pas le processus
            $this->logger?->warning('Erreur lors de la mise à jour de l\'activité utilisateur', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        } catch (InvalidArgumentException $e) {
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }
}