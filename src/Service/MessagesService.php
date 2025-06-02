<?php

namespace App\Service;

use App\Entity\Message;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MessagesService
{
    public function __construct(
        private readonly HubInterface $hub
    ) {}

    public function publishMessage(Message $message): void
    {
        $topic = sprintf('chat/conversation/%d-%d',
            min($message->getSender()->getId(), $message->getReceiver()->getId()),
            max($message->getSender()->getId(), $message->getReceiver()->getId())
        );

        $update = new Update(
            $topic,
            json_encode([
                'type' => 'message',
                'data' => [
                    'id' => $message->getId(),
                    'content' => $message->getContent(),
                    'sender' => $message->getSender()->getId(),
                    'receiver' => $message->getReceiver()->getId(),
                    'created_at' => $message->getCreatedAt()->format('c')
                ]
            ])
        );

        $this->hub->publish($update);
    }
}