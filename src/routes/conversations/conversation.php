<?php
require_once __DIR__ . '/../../controllers/conversation-controller.php';
function getUserConversations()
{
  $conversationControler = new Conversation_Controller();

  $result = $conversationControler->getUserConversations($GLOBALS['userId']);

  header('Content-Type: application/json');
  echo json_encode($result);
  exit;
}

switch ($request_method) {
  case 'GET':
    getUserConversations();
  default:
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Invalid request method.']);
    exit();
}
