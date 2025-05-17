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
    return $stmt->execute([
      $senderId,
      $conversation_id,
      $content
    ]);
  }
}
