<?php
require_once __DIR__ . '/./base-controller.php';
require_once __DIR__ . '/../repositories/conversation-repository.php';
require_once __DIR__ . '/../repositories/message-repository.php';

class Message_Controller extends Base_Controller
{
  private $conversationRepository;
  private $messageRepository;

  public function __construct()
  {
    $this->conversationRepository = new Conversation_Repository();
    $this->messageRepository = new Message_Repository();
  }

  public function sendMessage(int $senderId)
  {
    try {
      $this->messageRepository->startTransaction();

      $data = json_decode(file_get_contents('php://input'), true);

      $conversationId = $data['conversationId'] ?? null;
      $content = $data['content'] ?? null;
      $isGroup = $data['isGroup'] ?? null;

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

      if (!$isGroup) {
        throw new Exception(
          "Conversation type is required.",
          422,
          new Exception('isGroup')
        );
      }

      $conversation = $this->conversationRepository->getConversationById($conversationId);

      // Create new conversation if there's none
      if (!$conversation) {
        $conversation = $this->conversationRepository->createConversation(
          $isGroup,
          null
        );
      }
    } catch (Exception $e) {
      $this->messageRepository->rollbackTransaction();
      return $this->handleException($e);
    }
  }

  public function sendMessageToGroup(int $senderId)
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

      $message = $this->messageRepository->insertMessage(
        $senderId,
        $conversationId,
        $content
      );

      if (!$message) {
        throw new Exception('An error occured while sending message.', 400);
      }

      $this->messageRepository->commitTransaction();

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

  public function sendMessageToUser(int $senderId)
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

      // Create new conversation if there's none
      if (!$conversation) {
        $conversation = $this->conversationRepository->createConversation(
          0,
          null
        );
      }

      $message = $this->messageRepository->insertMessage(
        $senderId,
        $conversationId,
        $content
      );

      if (!$message) {
        throw new Exception('An error occured while sending message.', 400);
      }

      $this->messageRepository->commitTransaction();

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
