<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageForm;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\MessagesService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chat')]
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

    /**
     * Affiche la liste des utilisateurs avec lesquels l'utilisateur actuel peut chatter.
     *
     * @return Response Réponse HTTP avec le rendu de la liste des utilisateurs.
     * @throws Exception
     */
    #[Route('/', name: 'chat_index')]
    public function index(): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $users = $this->userRepository->findAllExcept($currentUser);

        $usersWithLastMessage = array_map(function ($user) use ($currentUser) {
            return [
                'user' => $user,
                'lastMessage' => $this->messageRepository->findLastMessageBetweenUsers($currentUser, $user)
            ];
        }, $users);

        usort($usersWithLastMessage, function ($a, $b) {
            $aDate = $a['lastMessage']?->getCreatedAt();
            $bDate = $b['lastMessage']?->getCreatedAt();
            return ($bDate ?? new \DateTimeImmutable('1970-01-01')) <=> ($aDate ?? new \DateTimeImmutable('1970-01-01'));
        });

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

        $message = new Message();
        $form = $this->createForm(MessageForm::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setSender($currentUser)
                ->setReceiver($user)
                ->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($message);
            $this->entityManager->flush();

            $this->messagesService->publishMessage($message);
            $this->notificationService->sendMessageNotification($message);

            return $this->redirectToRoute('chat_conversation', ['id' => $user->getId()]);
        }

        return $this->render('chat/conversation.html.twig', [
            'messages' => $this->messageRepository->findConversation($currentUser, $user),
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

    #[Route('/api/messages/{id}', name: 'chat_api_messages', methods: ['GET'])]
    public function getMessages(User $user, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
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
            'messages' => array_map(fn(Message $m) => [
                'id' => $m->getId(),
                'content' => $m->getContent(),
                'sender_id' => $m->getSender()->getId(),
                'sender_name' => $m->getSender()->getUsername(),
                'receiver_id' => $m->getReceiver()->getId(),
                'created_at' => $m->getCreatedAt()->format('c')
            ], $messages),
            'hasMore' => ($page * $limit) < $totalMessages,
            'currentPage' => $page
        ]);
    }
}