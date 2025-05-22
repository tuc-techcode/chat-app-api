<?php
require_once __DIR__ . '/./base-controller.php';
require_once __DIR__ . '/../repositories/conversation-repository.php';
require_once __DIR__ . '/../repositories/user-repository.php';
require_once __DIR__ . '/../services/pusher-service.php';

class Conversation_Controller extends Base_Controller
{
  private $conversationRepository;
  private $userRepository;
  private $pusherService;

  public function __construct()
  {
    $this->conversationRepository = new Conversation_Repository();
    $this->userRepository = new User_Repository();
    $this->pusherService = new Pusher_Service();
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

  public function createGroup($user_id)
  {
    try {
      $this->conversationRepository->startTransaction();

      $data = json_decode(file_get_contents('php://input'), true);

      $name = $data['groupName'] ?? null;
      $participants = $data['selectedParticipants'] ?? [];

      if (!$name) {
        throw new Exception(
          "Group name is required.",
          422,
          new Exception('name')
        );
      }

      // Validate participants (must be array and not empty)
      if (!is_array($participants) || empty($participants)) {
        throw new Exception("At least one participant is required.", 422);
      }



      $conversation = $this->conversationRepository->createConversation(
        true,
        $name
      );

      // Add creator as participant
      $this->conversationRepository->addConversationParticipant(
        $conversation['id'],
        $user_id
      );

      // Add other participants
      foreach ($participants as $participant_id) {
        // Validate participant exists
        if (!$this->userRepository->userExists($participant_id)) {
          throw new Exception("Invalid participant ID: $participant_id", 422);
        }

        // Prevent adding duplicate participants
        if (!$this->conversationRepository->isConversationParticipant($conversation['id'], $participant_id)) {
          $this->conversationRepository->addConversationParticipant(
            $conversation['id'],
            $participant_id
          );
        }
      }

      $this->conversationRepository->commitTransaction();

      // TODO: add notification and trigger pusher event

      // Trigger Pusher event for each participant
      $this->pusherService->trigger(
        'user-' . $user_id,
        'new-group',
        [
          'group_id' => $conversation['id'],
          'group_name' => $name,
          'participants' => array_merge([$user_id], $participants)
        ]
      );
      foreach ($participants as $participant_id) {
        $this->pusherService->trigger(
          'user-' . $participant_id,
          'new-group',
          [
            'group_id' => $conversation['id'],
            'group_name' => $name,
            'participants' => array_merge([$user_id], $participants)
          ]
        );
      }

      return $this->response([
        'error' => false,
        'data' => $conversation['id'],
        'message' => "Group created successfully."
      ], 201);
    } catch (Exception $e) {
      $this->conversationRepository->rollbackTransaction();
      return $this->handleException($e);
    }
  }
}
