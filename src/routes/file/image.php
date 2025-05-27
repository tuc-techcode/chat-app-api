<?php
require_once __DIR__ . '/../../controllers/file-controller.php';
function getImage()
{
  $fileControler = new File_Controller();

  $fileControler->getImage();
}

switch ($request_method) {
  case 'GET':
    getImage();
  default:
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Invalid request method.']);
    exit();
}
