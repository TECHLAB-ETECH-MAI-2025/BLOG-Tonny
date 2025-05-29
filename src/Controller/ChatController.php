<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageForm;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour gérer les fonctionnalités de chat.
 */
#[Route('/chat')]
#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepository $messageRepository,
        private UserRepository $userRepository,
        private HubInterface $hub,
    ) {}

    /**
     * Affiche la liste des utilisateurs avec lesquels l'utilisateur actuel peut chatter.
     *
     * @return Response Réponse HTTP avec le rendu de la liste des utilisateurs.
     */
    #[Route('/', name: 'chat_index')]
    public function index(): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $users = $this->userRepository->findAllExcept($currentUser);

        $usersWithLastMessage = array_map(function ($user) use ($currentUser) {
            $lastMessage = $this->messageRepository->findLastMessageBetweenUsers($currentUser, $user);
            return [
                'user' => $user,
                'lastMessage' => $lastMessage
            ];
        }, $users);

        return $this->render('chat/index.html.twig', [
            'usersWithLastMessage' => $usersWithLastMessage,
            'current_user' => $currentUser
        ]);
    }

    /**
     * Affiche la conversation entre l'utilisateur actuel et un autre utilisateur.
     *
     * @param User $user L'utilisateur avec lequel chatter.
     * @param Request $request Requête HTTP.
     * @return Response Réponse HTTP avec le rendu de la conversation.
     */
    #[Route('/conversation/{id}', name: 'chat_conversation')]
    public function conversation(User $user, Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($user === $currentUser) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas chatter avec vous-même.');
        }

        // Récupérer la conversation existante
        $messages = $this->messageRepository->findConversation($currentUser, $user);

        $message = new Message();
        $form = $this->createForm(MessageForm::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setSender($currentUser);
            $message->setReceiver($user);
            $message->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($message);
            $this->entityManager->flush();

            // Publier via Mercure
            $this->publishMessage($message);

            return $this->redirectToRoute('chat_conversation', ['id' => $user->getId()]);
        }

        return $this->render('chat/conversation.html.twig', [
            'messages' => $messages,
            'other_user' => $user,
            'current_user' => $currentUser,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Envoie un message via l'API.
     *
     * @param Request $request Requête HTTP.
     * @return JsonResponse Réponse JSON avec le statut de l'envoi du message.
     */
    #[Route('/api/send', name: 'chat_api_send', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $data = json_decode($request->getContent(), true);

        if (!isset($data['content']) || !isset($data['receiver_id'])) {
            return new JsonResponse(['error' => 'Données manquantes'], 400);
        }

        $receiver = $this->userRepository->find($data['receiver_id']);
        if (!$receiver) {
            return new JsonResponse(['error' => 'Destinataire introuvable'], 404);
        }

        if ($receiver === $currentUser) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas vous envoyer un message'], 400);
        }

        $content = trim($data['content']);
        if (empty($content)) {
            return new JsonResponse(['error' => 'Le message ne peut pas être vide'], 400);
        }

        $message = new Message();
        $message->setSender($currentUser);
        $message->setReceiver($receiver);
        $message->setContent($content);
        $message->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Publier via Mercure
        $this->publishMessage($message);

        return new JsonResponse([
            'status' => 'success',
            'message' => [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'sender_id' => $message->getSender()->getId(),
                'receiver_id' => $message->getReceiver()->getId(),
                'created_at' => $message->getCreatedAt()->format('c')
            ]
        ]);
    }

    /**
     * Récupère les messages d'une conversation via l'API.
     *
     * @param User $user L'utilisateur avec lequel récupérer les messages.
     * @return JsonResponse Réponse JSON avec les messages de la conversation.
     */
    #[Route('/api/messages/{id}', name: 'chat_api_messages')]
    public function getMessages(User $user): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $messages = $this->messageRepository->findConversation($currentUser, $user);

        $messagesData = array_map(function (Message $message) {
            return [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'sender_id' => $message->getSender()->getId(),
                'sender_name' => $message->getSender()->getUsername(),
                'receiver_id' => $message->getReceiver()->getId(),
                'created_at' => $message->getCreatedAt()->format('c')
            ];
        }, $messages);

        return new JsonResponse($messagesData);
    }

    /**
     * Publie un message via Mercure.
     *
     * @param Message $message Le message à publier.
     */
    private function publishMessage(Message $message): void
    {
        $messageData = [
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'sender_id' => $message->getSender()->getId(),
            'sender_name' => $message->getSender()->getUsername(),
            'receiver_id' => $message->getReceiver()->getId(),
            'created_at' => $message->getCreatedAt()->format('c')
        ];

        // Topic pour la conversation spécifique
        $conversationTopic = sprintf(
            'chat/conversation/%d-%d',
            min($message->getSender()->getId(), $message->getReceiver()->getId()),
            max($message->getSender()->getId(), $message->getReceiver()->getId())
        );

        $update = new Update(
            $conversationTopic,
            json_encode([
                'type' => 'new_message',
                'message' => $messageData
            ])
        );

        $this->hub->publish($update);
    }
}
