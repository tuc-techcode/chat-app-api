<?php
require_once __DIR__ . '/../../controllers/conversation-controller.php';
function getConversationMessages()
{
  $conversationControler = new Conversation_Controller();

  $conversation_id = $_GET['conversation_id'] ?? null;
  $limit = $_GET['limit'] ?? 50;
  $cursor = $_GET['cursor'] ?? null;

  $result = $conversationControler->getConversationMessages(
    $GLOBALS['userId'],
    $conversation_id,
    $limit,
    $cursor
  );

  header('Content-Type: application/json');
  echo json_encode($result);
  exit;
}

switch ($request_method) {
  case 'GET':
    getConversationMessages();
  default:
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Invalid request method.']);
    exit();
}
