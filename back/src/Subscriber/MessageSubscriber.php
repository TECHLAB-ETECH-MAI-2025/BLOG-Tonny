<?php
//namespace App\Subscriber;
//
//use App\Repository\MessageRepository;
//use Doctrine\Common\EventSubscriber;
//use Symfony\Component\EventDispatcher\EventSubscriberInterface;
//use Symfony\Component\Mercure\HubInterface;
//use Symfony\Component\Mercure\Update;
//use Symfony\Component\Serializer\SerializerInterface;
//
//class MessageSubscriber implements EventSubscriberInterface
//{
//    public function __construct(private  readonly  SerializerInterface $serializer, private  readonly HubInterface $hub){
//
//    }
//
//    public static function getSubscribedEvents(): array
//    {
//        return [
//            MessageEvent::class => 'onPublishedMessage'
//        ];
//    }
//    public function onPublishedMessage(MessageEvent $messageEvent): void
//    {
//        $message = $messageEvent->getMessage();
//        $channel = $message->getConversation()->getId();
//        $update = new Update("/");
//    }
//
//}