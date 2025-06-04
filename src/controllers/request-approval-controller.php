<?php
require_once __DIR__ . '/./base-controller.php';
require_once __DIR__ . '/../repositories/request-approval-repository.php';

class Request_Approval_Controller extends Base_Controller
{
  private $requestApprovalRepository;

  public function __construct()
  {
    $this->requestApprovalRepository = new Request_Approval_Repository();
  }

  public function getPendingApprovals(int $auth_user_id, int $limit, $cursor = null)
  {
    try {
      $data = $this->requestApprovalRepository->getPendingApprovals(
        $auth_user_id,
        $limit,
        $cursor
      );

      // Determine next cursor
      $nextCursor = null;

      if (count($data) === $limit) {

        $lastMessage = end($data);
        $nextCursor = $lastMessage['id']; // Use the last message ID as the next cursor
      }

      return $this->response([
        'error' => false,
        'data' => [
          'data' => $data,
          'nextCursor' => $nextCursor
        ],
        'message' => "Pending request approval fetched successfully."
      ], 200);
    } catch (Exception $e) {
      return $this->handleException($e);
    }
  }

  public function getUserPendingApprovals(int $userId, int $limit, $cursor = null)
  {
    try {
      $data = $this->requestApprovalRepository->getUserPendingRequestApprovals(
        $userId,
        $limit,
        $cursor
      );

      $nextCursor = null;

      if (count($data) === $limit) {
        $lastMessage = end($data);
        $nextCursor = $lastMessage['id'];
      }

      return $this->response([
        'error' => false,
        'data' => [
          'data' => $data,
          'nextCursor' => $nextCursor
        ],
        'message' => "My Pending request fetched successfully."
      ]);
    } catch (Exception $e) {
      return $this->handleException($e);
    }
  }

  public function getUserCompletedRequestApprovals(int $userId, int $limit, $cursor = null)
  {
    try {
      $data = $this->requestApprovalRepository->getUserCompletedRequestApprovals(
        $userId,
        $limit,
        $cursor
      );

      $nextCursor = null;

      if (count($data) === $limit) {
        $lastMessage = end($data);
        $nextCursor = $lastMessage['id'];
      }

      return $this->response([
        'error' => false,
        'data' => [
          'data' => $data,
          'nextCursor' => $nextCursor
        ],
        'message' => "My completed request fetched successfully."
      ]);
    } catch (Exception $e) {
      return $this->handleException($e);
    }
  }
}
