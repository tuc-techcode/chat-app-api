<?php
require_once __DIR__ . '/../../controllers/conversation-controller.php';
function getConversationDetails()
{
  $conversationControler = new Conversation_Controller();

  $result = $conversationControler->getConversationDetails(
    $GLOBALS['paramId'],
    $GLOBALS['userId'],
  );

  header('Content-Type: application/json');
  echo json_encode($result);
  exit;
}

switch ($request_method) {
  case 'GET':
    getConversationDetails();
  default:
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Invalid request method.']);
    exit();
}
