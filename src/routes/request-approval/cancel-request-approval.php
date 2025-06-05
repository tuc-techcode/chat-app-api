<?php
require_once __DIR__ . '/../../controllers/request-approval-controller.php';
function cancelRequestApproval()
{
  $requestApprovalControler = new Request_Approval_Controller();

  $result = $requestApprovalControler->cancelRequestApproval(
    $GLOBALS['userId'],
  );

  header('Content-Type: application/json');
  echo json_encode($result);
  exit;
}

switch ($request_method) {
  case 'PATCH':
    cancelRequestApproval();
  default:
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Invalid request method.']);
    exit();
}
