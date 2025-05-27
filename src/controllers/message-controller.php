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
      $images = $data['images'] ?? [];

      if (!$conversationId) {
        throw new Exception(
          "Conversation ID is required.",
          422,
          new Exception('conversationId')
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


      foreach ($images as $imageBase64) {
        if (!empty($imageBase64)) {
          $fileUrl = $this->saveBase64Image($imageBase64);

          $this->messageRepository->saveMessageAttachments(
            $message['id'],
            $fileUrl,
            'image'
          );
        }
      }

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


  /**
   * Saves a base64 encoded image to the attachments folder organized by month-year
   */
  private function saveBase64Image(string $base64Image): string
  {
    // Extract the image data and extension from base64 string
    if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
      $imageType = strtolower($matches[1]);
      $imageData = substr($base64Image, strpos($base64Image, ',') + 1);
    } else {
      throw new Exception("Invalid base64 image format", 400);
    }

    // Validate image type
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($imageType, $allowedTypes)) {
      throw new Exception("Unsupported image type: $imageType", 400);
    }

    // Decode the base64 data
    $decodedImage = base64_decode($imageData);
    if ($decodedImage === false) {
      throw new Exception("Failed to decode base64 image", 400);
    }

    // Create month-year folder structure (format: m-Y)
    $currentDate = new DateTime();
    $monthYearFolder = $currentDate->format('m-Y'); // e.g. "12-2023"
    $storagePath = __DIR__ . '/../../storage/attachments/' . $monthYearFolder;

    // Create directories if they don't exist
    if (!file_exists($storagePath)) {
      mkdir($storagePath, 0755, true);
    }

    // Generate unique filename with extension
    $filename = 'img_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $imageType;
    $filePath = $storagePath . '/' . $filename;

    // Save the file
    if (file_put_contents($filePath, $decodedImage) === false) {
      throw new Exception("Failed to save image", 500);
    }

    // Return relative path for database storage (including month-year folder)
    return 'storage/attachments/' . $monthYearFolder . '/' . $filename;
  }
}
