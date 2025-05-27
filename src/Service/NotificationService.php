<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\User;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Service pour gérer les notifications de messages en temps réel
 */
readonly class NotificationService
{
    public function __construct(
        private HubInterface $hub
    ) {}

    /**
     * Envoie une notification de nouveau message à l'utilisateur destinataire
     */
    public function sendMessageNotification(Message $message): void
    {
        $notificationTopic = sprintf('user/%d/notifications', $message->getReceiver()->getId());

        $notificationData = [
            'type' => 'new_message_notification',
            'message' => [
                'id' => $message->getId(),
                'content' => $this->truncateMessage($message->getContent()),
                'sender_id' => $message->getSender()->getId(),
                'sender_name' => $message->getSender()->getUsername(),
                'created_at' => $message->getCreatedAt()->format('c')
            ]
        ];

        $update = new Update(
            $notificationTopic,
            json_encode($notificationData)
        );

        $this->hub->publish($update);
    }

    /**
     * Tronque le message pour l'affichage dans la notification
     */
    private function truncateMessage(string $content, int $maxLength = 50): string
    {
        if (mb_strlen($content) <= $maxLength) {
            return $content;
        }

        return mb_substr($content, 0, $maxLength) . '...';
    }
}