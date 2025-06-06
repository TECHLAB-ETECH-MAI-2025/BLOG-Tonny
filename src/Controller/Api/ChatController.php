<?php

namespace App\Controller\Api;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\MessagesService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/chat')]
#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepository $messageRepository,
        private UserRepository $userRepository,
        private NotificationService $notificationService,
        private MessagesService $messagesService,
    ) {}

    #[Route('/', name: 'api_chat_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $users = $this->userRepository->findAllExcept($currentUser);

        $usersWithLastMessage = array_map(function ($user) use ($currentUser) {
            return [
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    // Ajoutez d'autres champs utilisateur si nécessaire
                ],
                'lastMessage' => $this->messageRepository->findLastMessageBetweenUsers($currentUser, $user)
                    ? [
                        'id' => $this->messageRepository->findLastMessageBetweenUsers($currentUser, $user)->getId(),
                        'content' => $this->messageRepository->findLastMessageBetweenUsers($currentUser, $user)->getContent(),
                        'created_at' => $this->messageRepository->findLastMessageBetweenUsers($currentUser, $user)->getCreatedAt()->format('c')
                    ]
                    : null
            ];
        }, $users);

        usort($usersWithLastMessage, function ($a, $b) {
            $aDate = $a['lastMessage']['created_at'] ?? null;
            $bDate = $b['lastMessage']['created_at'] ?? null;
            return ($bDate ?? '1970-01-01') <=> ($aDate ?? '1970-01-01');
        });

        return new JsonResponse([
            'usersWithLastMessage' => $usersWithLastMessage,
            'current_user' => [
                'id' => $currentUser->getId(),
                'username' => $currentUser->getUsername()
            ]
        ]);
    }

    #[Route('/send', name: 'api_chat_send', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['content']) || !isset($data['receiver_id'])) {
            return new JsonResponse(['error' => 'Données manquantes'], 400);
        }

        $receiver = $this->userRepository->find($data['receiver_id']);
        if (!$receiver || $receiver === $currentUser) {
            return new JsonResponse(['error' => 'Destinataire invalide'], 400);
        }

        $message = (new Message())
            ->setSender($currentUser)
            ->setReceiver($receiver)
            ->setContent(trim($data['content']))
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $this->messagesService->publishMessage($message);
        $this->notificationService->sendMessageNotification($message);

        return new JsonResponse([
            'status' => 'success',
            'message' => [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'sender_id' => $currentUser->getId(),
                'receiver_id' => $receiver->getId(),
                'created_at' => $message->getCreatedAt()->format('c')
            ]
        ]);
    }

    #[Route('/messages/{id}', name: 'api_chat_messages', methods: ['GET'])]
    public function getMessages(User $user, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($user->getId() === $currentUser->getId()) {
            return new JsonResponse(['error' => 'Cannot chat with yourself'], 400);
        }

        $page = $request->query->getInt('page', 1);
        $limit = 20;

        $messages = $this->messageRepository->findPaginatedConversation(
            $currentUser,
            $user,
            $page,
            $limit
        );

        $totalMessages = $this->messageRepository->countConversationMessages($currentUser, $user);

        return new JsonResponse([
            'messages' => array_map(function (Message $m) {
                return [
                    'id' => $m->getId(),
                    'content' => $m->getContent(),
                    'sender_id' => $m->getSender()->getId(),
                    'sender_name' => $m->getSender()->getUsername(),
                    'receiver_id' => $m->getReceiver()->getId(),
                    'created_at' => $m->getCreatedAt()->format('c')
                ];
            }, $messages),
            'hasMore' => ($page * $limit) < $totalMessages,
            'currentPage' => $page
        ]);
    }
}