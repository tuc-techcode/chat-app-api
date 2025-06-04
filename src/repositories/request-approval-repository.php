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
        
        -- Requester info (person who made the request)
        requester.user_id AS requester_id,
        requester.username AS requester_username,
        requester.avatar_url AS requester_avatar,
        requester.first_name AS requester_first_name,
        requester.last_name AS requester_last_name,
        
        -- Approver info (current user viewing these)
        approver.user_id AS approver_id,
        approver.username AS approver_username,
        approver.avatar_url AS approver_avatar,
        approver.first_name AS approver_first_name,
        approver.last_name AS approver_last_name,
        
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
        users requester ON ra.requester_id = requester.user_id
    JOIN 
        users approver ON ra.approver_id = approver.user_id
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

  public function getUserPendingRequestApprovals(int $requesterId, int $limit = 30, $cursor = null)
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
        
        -- Requester info (current user)
        requester.user_id AS requester_id,
        requester.username AS requester_username,
        requester.avatar_url AS requester_avatar,
        requester.first_name AS requester_first_name,
        requester.last_name AS requester_last_name,
        
        -- Approver info
        approver.user_id AS approver_id,
        approver.username AS approver_username,
        approver.avatar_url AS approver_avatar,
        approver.first_name AS approver_first_name,
        approver.last_name AS approver_last_name,
        
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
        users requester ON ra.requester_id = requester.user_id
    JOIN 
        users approver ON ra.approver_id = approver.user_id
    WHERE 
        ra.requester_id = :requester_id
    AND ra.status = 'pending'"
      . ($cursor ? " AND ra.id < :cursor" : "") . "
    ORDER BY 
        ra.request_date DESC
    LIMIT :limit";

    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':requester_id', $requesterId, PDO::PARAM_INT);
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

  public function getUserCompletedRequestApprovals(int $requesterId, int $limit = 30, $cursor = null)
  {
    $sql = "SELECT 
        ra.id,
        ra.message_id,
        ra.priority_level,
        ra.status,
        ra.request_date,
        ra.approval_date,
        ra.title,
        ra.comments,
        ra.updated_at,
        m.content AS message_content,
        m.created_at AS message_created_at,
        
        -- Requester info (current user)
        requester.user_id AS requester_id,
        requester.username AS requester_username,
        requester.avatar_url AS requester_avatar,
        requester.first_name AS requester_first_name,
        requester.last_name AS requester_last_name,
        
        -- Approver info
        approver.user_id AS approver_id,
        approver.username AS approver_username,
        approver.avatar_url AS approver_avatar,
        approver.first_name AS approver_first_name,
        approver.last_name AS approver_last_name,
        
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
        users requester ON ra.requester_id = requester.user_id
    JOIN 
        users approver ON ra.approver_id = approver.user_id
    WHERE 
        ra.requester_id = :requester_id
        AND ra.status IN ('approved', 'rejected', 'cancelled')"
      . ($cursor ? " AND ra.id < :cursor" : "") . "
    ORDER BY 
        COALESCE(ra.approval_date, ra.updated_at) DESC
    LIMIT :limit";

    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':requester_id', $requesterId, PDO::PARAM_INT);
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
