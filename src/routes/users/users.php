<?php
require_once __DIR__ . '/../../controllers/user-controller.php';
function getUserContacts()
{
  $userControler = new User_Controller();

  $result = $userControler->getUserContacts($GLOBALS['userId']);

  header('Content-Type: application/json');
  echo json_encode($result);
  exit;
}

switch ($request_method) {
  case 'GET':
    getUserContacts();
  default:
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Invalid request method.']);
    exit();
}
