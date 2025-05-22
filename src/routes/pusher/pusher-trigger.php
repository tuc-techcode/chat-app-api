<?php
require_once __DIR__ . '/../../controllers/pusher-controller.php';
function trigger()
{
  $pusherControler = new Pusher_Controller();

  $result = $pusherControler->trigger();

  header('Content-Type: application/json');
  echo json_encode($result);
  exit;
}

switch ($request_method) {
  case 'POST':
    trigger();
  default:
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Invalid request method.']);
    exit();
}
