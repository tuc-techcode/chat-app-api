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
}
