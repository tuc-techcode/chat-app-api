<?php
require_once __DIR__ . '/./base-repository.php';

class Message_Repository extends Base_Repository
{
  public function insertMessage(int $senderId, int $conversation_id, string $content)
  {
    $sql = "INSERT INTO 
              messages 
              (sender_id, conversation_id, content)
            VALUES
              (?, ?, ?)";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([
      $senderId,
      $conversation_id,
      $content
    ]);

    $messageId = $this->db->lastInsertId();

    return $this->getMessageById($messageId);
  }

  private function getMessageById(int $messageId)
  {
    $sql = "SELECT 
                    m.id,
                    m.content,
                    m.created_at,
                    m.sender_id,
                    u.username,
                    u.avatar_url,
                    u.first_name,
                    u.last_name
                FROM 
                    messages m
                JOIN 
                    users u ON m.sender_id = u.user_id
                WHERE 
                    m.id = ?";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$messageId]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function setMessageStatus(int $conversationId, int $messageId, int $userId, string $status = 'unread')
  {
    $sql = "INSERT INTO message_status
            (conversation_id, message_id, user_id, status)
            VALUES
            (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            status = VALUES(status)";

    $stmt = $this->db->prepare($sql);

    return $stmt->execute([
      $conversationId,
      $messageId,
      $userId,
      $status
    ]);
  }

  public function saveMessageAttachments(int $messageId, string $fileUrl, string $fileType)
  {

    $allowedTypes = ['image', 'video', 'audio', 'file'];
    if (!in_array($fileType, $allowedTypes)) {
      throw new Exception("Invalid file type: $fileType", 400);
    }

    $sql = "INSERT INTO message_attachments
            (message_id, file_url, file_type)
            VALUES
            (?, ?, ?)";

    $stmt = $this->db->prepare($sql);

    return $stmt->execute([
      $messageId,
      $fileUrl,
      $fileType
    ]);
  }
}
