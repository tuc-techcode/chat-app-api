<?php
require_once __DIR__ . '/../../controllers/request-approval-controller.php';
function getPendingApprovals()
{
  $requestApprovalControler = new Request_Approval_Controller();

  $limit = $_GET['limit'] ?? 30;
  $cursor = $_GET['cursor'] ?? null;

  $result = $requestApprovalControler->getPendingApprovals(
    $GLOBALS['userId'],
    $limit,
    $cursor
  );

  header('Content-Type: application/json');
  echo json_encode($result);
  exit;
}

switch ($request_method) {
  case 'GET':
    getPendingApprovals();
  default:
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Invalid request method.']);
    exit();
}
