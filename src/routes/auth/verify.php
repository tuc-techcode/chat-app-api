<?php
require_once __DIR__ . '/../../controllers/auth-controller.php';
function verifyToken()
{
  $authControler = new Auth_Controller();

  $result = $authControler->verifyToken();

  header('Content-Type: application/json');
  echo json_encode($result);
  exit;
}

switch ($request_method) {
  case 'GET':
    verifyToken();
  default:
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Invalid request method.']);
    exit();
}
