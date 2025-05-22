<?php
require_once __DIR__ . '/./base-repository.php';

class User_Repository extends Base_Repository
{
  public function getUserByUsername($username)
  {
    $sql = "SELECT * FROM users WHERE username = :username";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['username' => $username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function getUserById($user_id)
  {
    $sql = "SELECT * FROM users WHERE user_id = :user_id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function userExists(int $user_id): bool
  {
    $sql = "SELECT COUNT(*) FROM users WHERE user_id = :user_id AND is_active = 1";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);

    return (bool) $stmt->fetchColumn();
  }

  public function createUser($username, $password_hash, $first_name, $last_name, $gender, $avatar_url = null)
  {
    $sql = "INSERT INTO users (username, password_hash, first_name, last_name, gender, avatar_url)
            VALUES (:username, :password_hash, :first_name, :last_name, :gender, :avatar_url)";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([
      'username' => $username,
      'password_hash' => $password_hash,
      'first_name' => $first_name,
      'last_name' => $last_name,
      'gender' => $gender,
      'avatar_url' => $avatar_url
    ]);

    // Fetch and return the newly created user
    $user_id = $this->db->lastInsertId();
    $query = $this->db->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $query->execute(['user_id' => $user_id]);
    return $query->fetch(PDO::FETCH_ASSOC);
  }
}
