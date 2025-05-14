<?php
require_once __DIR__ . '/../helpers/jwt-token-helper.php';
require_once __DIR__ . '/../repositories/user-repository.php';
class AuthMiddleware
{
  private $jwtHelper;
  private $userRepository;

  public function __construct()
  {
    $this->jwtHelper = new Jwt_Token_Helper;
    $this->userRepository = new User_Repository;
  }

  public function handle()
  {
    try {
      // Get and validate token from headers
      $headers = getallheaders();
      $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

      if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        throw new Exception("Authorization token not found", 401);
      }

      $token = $matches[1];

      // Validate token
      $decoded = $this->jwtHelper->validate($token, $this->jwtHelper->getSecretKey());

      // Get user and verify existence
      $user = $this->userRepository->getUserById($decoded->sub);
      if (!$user) {
        throw new Exception('User not found', 404);
      }

      // Generate new token (refresh)
      $newToken = $this->jwtHelper->generateToken($user['user_id']);

      // Set global user ID for route handlers
      global $userId;
      $userId = $user['user_id'];

      // Return new token in header
      header("Authorization: Bearer $newToken");

      // Continue to next middleware/route
      return true;
    } catch (Exception $e) {
      http_response_code($e->getCode() ?: 401);
      header('Content-Type: application/json');
      echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
      ]);
      exit();
    }
  }

  private function sanitizeUserData($user)
  {
    // Remove sensitive fields before returning user data
    unset($user['password_hash']);
    unset($user['is_active']);
    return $user;
  }
}
