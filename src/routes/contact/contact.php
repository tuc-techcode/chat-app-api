<?php
require_once __DIR__ . '/../../controllers/contact-controller.php';
function getUserContacts()
{
  $contactControler = new Contact_Controller();

  $result = $contactControler->getUserContacts($GLOBALS['paramUserId']);

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
