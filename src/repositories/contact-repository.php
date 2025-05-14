<?php
require_once __DIR__ . '/./base-repository.php';

class Contact_Repository extends Base_Repository
{
  public function getUserContacts($user_id)
  {
    $sql = "SELECT 
                    u.user_id,
                    u.username,
                    u.first_name,
                    u.last_name,
                    u.avatar_url,
                    u.status,
                    u.last_seen,
                    c.id,
                    c.nickname,
                    c.created_at AS contact_since
                FROM 
                    contacts c 
                JOIN 
                    users u ON c.contact_id = u.user_id
                WHERE 
                    c.user_id = :user_id
                ORDER BY
                    u.status DESC,
                    u.last_seen DESC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);

    // Changed to fetchAll to get multiple contacts
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function addContact($user_id, $contact_id)
  {
    $sql = "INSERT INTO contacts
            (user_id, contact_id)
            VALUES (:user_id, :contact_id)";

    $stmt = $this->db->prepare($sql);

    return  $stmt->execute([
      'user_id' => $user_id,
      'contact_id' => $contact_id
    ]);
  }

  public function isContact($user_id, $contact_id)
  {
    $sql = "SELECT id, FROM contacts WHERE user_id = :user_id AND contact_id = :contact_id";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['user_id' => $user_id, 'contact_id' => $contact_id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }
}
