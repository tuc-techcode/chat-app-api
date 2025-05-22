<?php
require_once __DIR__ . '/../../controllers/pusher-controller.php';
function userAuth()
{
  $pusherControler = new Pusher_Controller();

  $result = $pusherControler->auth();

  header('Content-Type: application/json');
  echo json_encode($result);
  exit;
}

switch ($request_method) {
  case 'POST':
    userAuth();
  default:
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Invalid request method.']);
    exit();
}
