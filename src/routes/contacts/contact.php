<?php
require_once __DIR__ . '/../../controllers/contact-controller.php';
function getUserContacts()
{
  $contactController = new Contact_Controller();

  $result = $contactController->getUserContacts($GLOBALS['userId']);

  header('Content-Type: application/json');
  echo json_encode($result);
  exit;
}

function addContact()
{
  $contactController = new Contact_Controller();

  $result = $contactController->addContact($GLOBALS['userId']);

  header('Content-Type: application/json');
  echo json_encode($result);
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
