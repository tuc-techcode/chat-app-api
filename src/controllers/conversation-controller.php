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

  public function getConversationMessages(int $conversation_id, int $limit, $cursor = null)
  {
    try {
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
          'messages' => $messages,
          'nextCursor' => $nextCursor
        ],
        'message' => "Conversation messages fetched successfully."
      ], 200);
    } catch (Exception $e) {
      return $this->handleException($e);
    }
  }
}
