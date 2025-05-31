<?php
require_once __DIR__ . '/../../controllers/message-controller.php';
function sendRequestApproval()
{
  $messageControler = new Message_Controller();

  $result = $messageControler->sendRequestApproval(
    $GLOBALS['userId'],
  );

  header('Content-Type: application/json');
  echo json_encode($result);
  exit;
}

switch ($request_method) {
  case 'POST':
    sendRequestApproval();
  default:
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Invalid request method.']);
    exit();
}
