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
    $sql = "SELECT id FROM contacts WHERE user_id = :user_id AND contact_id = :contact_id";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['user_id' => $user_id, 'contact_id' => $contact_id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function searchAllUsers(int $currentUserId, string $search)
  {
    $sql = "SELECT 
              u.user_id,
              u.username,
              u.first_name,
              u.last_name,
              u.avatar_url,
              u.status,
              CASE 
                WHEN EXISTS (
                  SELECT 1 FROM contacts c 
                  WHERE c.user_id = ? 
                  AND c.contact_id = u.user_id
                ) THEN 1
                ELSE 0
              END AS is_contact
            FROM users u
            WHERE
              (u.username LIKE ?
              OR u.first_name LIKE ?
              OR u.last_name LIKE ?)
              AND u.user_id != ?
              AND u.is_active = 1
            ORDER BY
              is_contact DESC,  -- Contacts appear first
              u.first_name ASC
            LIMIT 10";

    $stmt = $this->db->prepare($sql);
    $searchParam = "%$search%";
    $stmt->execute([
      $currentUserId,    // For the contact check
      $searchParam,      // username LIKE
      $searchParam,      // first_name LIKE
      $searchParam,      // last_name LIKE
      $currentUserId     // user_id != current user
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
