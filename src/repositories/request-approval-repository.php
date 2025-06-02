<?php
require_once __DIR__ . '/./base-repository.php';

class Request_Approval_Repository extends Base_Repository
{
  public function createRequestApproval(int $messageId, int $requesterId, int $approverId, string $priorityLevel, $title, $comments = null)
  {
    $sql = "INSERT INTO request_approvals
            (message_id, requester_id, approver_id, priority_level, title, comments)
            VALUES
            (?, ?, ?, ?, ?, ?)";

    $stmt = $this->db->prepare($sql);

    return $stmt->execute([
      $messageId,
      $requesterId,
      $approverId,
      $priorityLevel,
      $title,
      $comments
    ]);
  }

  public function getPendingApprovals($approver_id, $limit = 30, $cursor = null)
  {
    $sql = "SELECT 
        ra.id,
        ra.message_id,
        ra.priority_level,
        ra.status,
        ra.request_date,
        ra.title,
        ra.comments,
        m.content AS message_content,
        m.created_at AS message_created_at,
        u.user_id AS requester_id,
        u.username AS requester_username,
        u.avatar_url AS requester_avatar,
        u.first_name AS requester_first_name,
        u.last_name AS requester_last_name,
        (
            SELECT GROUP_CONCAT(
                JSON_OBJECT(
                    'id', ma.id,
                    'file_url', ma.file_url,
                    'file_type', ma.file_type,
                    'original_name', ma.original_name
                ) SEPARATOR '||'
            )
            FROM message_attachments ma
            WHERE ma.message_id = m.id
        ) AS attachments_json
    FROM 
        request_approvals ra
    JOIN 
        messages m ON ra.message_id = m.id
    JOIN 
        users u ON ra.requester_id = u.user_id
    WHERE 
        ra.approver_id = :approver_id
        AND ra.status = 'pending'"
      . ($cursor ? " AND ra.id < :cursor" : "") . "
    ORDER BY 
        CASE ra.priority_level
            WHEN 'critical' THEN 1
            WHEN 'urgent' THEN 2
            WHEN 'normal' THEN 3
            WHEN 'not_urgent' THEN 4
            ELSE 5
        END,
        ra.request_date DESC
    LIMIT :limit";

    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':approver_id', $approver_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

    if ($cursor) {
      $stmt->bindParam(':cursor', $cursor, PDO::PARAM_INT);
    }

    $stmt->execute();
    $approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process approvals
    return array_map(function ($approval) {
      // Parse attachments
      $approval['attachments'] = !empty($approval['attachments_json'])
        ? array_map('json_decode', explode('||', $approval['attachments_json']))
        : [];

      // Clean up
      unset($approval['attachments_json']);

      return $approval;
    }, $approvals);
  }
}
