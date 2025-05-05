<?php
require_once __DIR__ . '/../../controllers/auth-controller.php';
function login()
{
  $authControler = new Auth_Controller();

  $result = $authControler->login();

  header('Content-Type: application/json');
  echo json_encode($result);
  exit;
}

switch ($request_method) {
  case 'POST':
    login();
  default:
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Invalid request method.']);
    exit();
}
