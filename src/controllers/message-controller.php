<?php
require_once __DIR__ . '/./base-controller.php';
require_once __DIR__ . '/../repositories/conversation-repository.php';
require_once __DIR__ . '/../repositories/message-repository.php';
require_once __DIR__ . '/../services/pusher-service.php';
require_once __DIR__ . '/../services/notification-service.php';

class Message_Controller extends Base_Controller
{
  private $conversationRepository;
  private $messageRepository;
  private $pusherService;
  private $expoNotificationService;

  public function __construct()
  {
    $this->conversationRepository = new Conversation_Repository();
    $this->messageRepository = new Message_Repository();
    $this->pusherService = new Pusher_Service();
    $this->expoNotificationService = new Expo_Notification_Service();
  }

  public function sendMessage(int $senderId)
  {
    try {
      $this->messageRepository->startTransaction();

      $data = json_decode(file_get_contents('php://input'), true);

      $conversationId = $data['conversationId'] ?? null;
      $content = $data['content'] ?? null;

      if (!$conversationId) {
        throw new Exception(
          "Conversation ID is required.",
          422,
          new Exception('conversationId')
        );
      }

      if (!$content) {
        throw new Exception(
          "Message content is required.",
          422,
          new Exception('content')
        );
      }

      $conversation = $this->conversationRepository->getConversationById($conversationId);

      if (!$conversation) {
        throw new Exception(
          "Conversation not found.",
          404,
          new Exception('conversationId')
        );
      }

      // check if sender is part of the conversation
      $isParticipant = $this->conversationRepository->isConversationParticipant($conversationId, $senderId);

      if (!$isParticipant) {
        throw new RuntimeException("User is not part of this conversation", 400);
      }

      $message = $this->messageRepository->insertMessage(
        $senderId,
        $conversationId,
        $content
      );

      if (!$message) {
        throw new Exception('An error occured while sending message.', 400);
      }

      $participants = $this->conversationRepository->getConversationParticipants(
        $conversationId
      );

      $isGroup = (bool) $conversation['is_group'] ?? false;

      $recipients = array_filter($participants, function ($participant) use ($senderId) {
        return $participant['id'] != $senderId;
      });

      $recipientsWithNotificationToken = array_filter($participants, function ($participant) use ($senderId) {
        return $participant['id'] != $senderId &&
          !empty($participant['notification_token']);
      });

      // set message status
      foreach ($recipients as $recipient) {
        $this->messageRepository->setMessageStatus(
          $conversationId,
          $message['id'],
          $recipient['id'],
          'unread'
        );
      }

      $this->messageRepository->commitTransaction();

      // pusher trigger
      foreach ($participants as $participant) {
        $this->pusherService->trigger(
          'user-' . $participant['id'],
          'new-message',
          [
            'isGroup' => $isGroup,
            'conversationId' => $conversationId,
            'senderId' => $senderId,
          ]
        );
      }

      // Send expo push notification
      $notificationTokens = array_column($recipientsWithNotificationToken, 'notification_token');
      if (!empty($notificationTokens)) {

        $notificationTitle = !$isGroup
          ? ($message['first_name'] . ' ' . $message['last_name'])
          : $conversation['name'];

        $messageContent = $isGroup
          ? $message['first_name'] . ' ' . $message['last_name'] . ': ' . $content
          : $content;

        $this->expoNotificationService->sendPushNotification(
          $notificationTokens,
          'default',
          $notificationTitle,
          $messageContent,
          [
            'isGroup' => $isGroup,
            'conversationId' => $conversationId,
            'senderId' => $senderId,
          ]
        );
      }

      return $this->response([
        'error' => false,
        'data' => $message,
        'message' => 'Message sent successfully.'
      ]);
    } catch (Exception $e) {
      $this->messageRepository->rollbackTransaction();
      return $this->handleException($e);
    }
  }
}
