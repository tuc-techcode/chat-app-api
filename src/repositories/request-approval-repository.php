<?php
require_once __DIR__ . '/./base-repository.php';

class Request_Approval_Repository extends Base_Repository
{
  public function createRequestApproval(int $messageId, int $requesterId, int $approverId, string $priorityLevel, $comments = null)
  {
    $sql = "INSERT INTO request_approvals
            (message_id, requester_id, approver_id, priority_level, comments)
            VALUES
            (?, ?, ?, ?, ?)";

    $stmt = $this->db->prepare($sql);

    return $stmt->execute([
      $messageId,
      $requesterId,
      $approverId,
      $priorityLevel,
      $comments
    ]);
  }
}
