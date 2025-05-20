<?php
require_once __DIR__ . '/./base-controller.php';
require_once __DIR__ . '/../repositories/conversation-repository.php';

class Conversation_Controller extends Base_Controller
{
  private $conversationRepository;

  public function __construct()
  {
    $this->conversationRepository = new Conversation_Repository();
  }

  public function getUserConversations($user_id)
  {
    try {
      $conversations = $this->conversationRepository->getUserConversations($user_id);

      return $this->response([
        'error' => false,
        'data' => $conversations,
        'message' => "Conversations fetch successfully."
      ], 200);
    } catch (Exception $e) {
      return $this->handleException($e);
    }
  }

  public function getConversationDetails($conversation_id, $user_id)
  {
    try {
      $conversationDetails = $this->conversationRepository->getConversationDetails(
        $conversation_id,
        $user_id
      );

      return $this->response([
        'error' => false,
        'data' => $conversationDetails,
        'message' => 'Conversation details fetch successfully.'
      ], 200);
    } catch (Exception $e) {
      return $this->handleException($e);
    }
  }

  public function getConversationMessages(int $auth_user_id, int $conversation_id, int $limit, $cursor = null)
  {
    try {
      $conversationDetails = $this->conversationRepository->getConversationDetails($conversation_id, $auth_user_id);

      $messages = $this->conversationRepository->getConversationMessages(
        $conversation_id,
        $limit,
        $cursor
      );

      // Determine next cursor
      $nextCursor = null;

      if (count($messages) === $limit) {

        $lastMessage = end($messages);
        $nextCursor = $lastMessage['id']; // Use the last message ID as the next cursor
      }

      return $this->response([
        'error' => false,
        'data' => [
          'conversationDetails' => $conversationDetails,
          'messages' => $messages,
          'nextCursor' => $nextCursor
        ],
        'message' => "Conversation messages fetched successfully."
      ], 200);
    } catch (Exception $e) {
      return $this->handleException($e);
    }
  }

  public function getDirectConversation(int $auth_user_id, int $user_id, int $limit, int $cursor)
  {
    try {
      $this->conversationRepository->startTransaction();
      if (!$user_id) {
        throw new Exception(
          "User ID is required",
          422,
          new Exception("user_id")
        );
      }

      $existingConversation = $this->conversationRepository->findDirectConversation(
        $auth_user_id,
        $user_id
      );

      $conversation = $existingConversation ?: $this->conversationRepository->createConversation(0, null);

      if (!$conversation) {
        throw new Exception(
          "An error occurred while creating a conversation.",
          500
        );
      }

      if (!$existingConversation) {
        $this->conversationRepository->addConversationParticipant(
          $conversation['id'],
          $auth_user_id
        );

        $this->conversationRepository->addConversationParticipant(
          $conversation['id'],
          $user_id
        );
      }

      $conversationDetails = $this->conversationRepository->getConversationDetails(
        $conversation['id'],
        $auth_user_id
      );

      $messages = $this->conversationRepository->getConversationMessages(
        $conversation['id'],
        $limit,
        $cursor
      );

      $nextCursor = null;

      if (count($messages) === $limit) {

        $lastMessage = end($messages);
        $nextCursor = $lastMessage['id'];
      }

      $this->conversationRepository->commitTransaction();

      return $this->response([
        'error' => false,
        'data' => [
          'conversationDetails' => $conversationDetails,
          'messages' => $messages,
          'nextCursor' => $nextCursor
        ],
        'message' => 'Message sent successfully.'
      ]);
    } catch (Exception $e) {
      $this->conversationRepository->rollbackTransaction();
      return $this->handleException($e);
    }
  }
}
