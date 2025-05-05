<?php
require_once __DIR__ . '/./base-controller.php';
require_once __DIR__ . '/../repositories/user-repository.php';
require_once __DIR__ . '/../helpers/jwt-token-helper.php';

class Auth_Controller extends Base_Controller
{
  private $userRepository;
  private $jwtHelper;

  public function __construct()
  {
    $this->userRepository = new User_Repository();
    $this->jwtHelper = new Jwt_Token_Helper();
  }

  public function login()
  {
    try {
      $data = json_decode(file_get_contents('php://input'), true);

      $username = isset($data['username']) ? trim($data['username']) : null;
      $password = isset($data['password']) ? trim($data['password']) : null;

      if (!$username || !$password) {
        throw new Exception("Username and password are required", 400);
      }

      if (!strlen($username) || !strlen($password)) {
        throw new Exception("Username and password are required", 400);
      }

      $user = $this->userRepository->getUserByUsername($username);

      if (!$user) {
        throw new Exception("User not found", 404);
      }

      if (!password_verify($password, $user['password_hash'])) {
        throw new Exception("Invalid password", 401);
      }

      $token = $this->jwtHelper->generateToken($user['user_id']);

      $safeUserData = $this->sanitizeUserData($user);

      return $this->response([
        'error' => false,
        'data' => [
          'user' => $safeUserData,
          'token' => $token
        ],
        'message' => "Login successful."
      ], 200);
    } catch (Exception $e) {
      return $this->handleException($e);
    }
  }

  public function createUser()
  {
    try {
      $data = json_decode(file_get_contents('php://input'), true);

      $username = $data['username'] ?? null;
      $password = $data['password'] ?? null;
      $first_name = $data['first_name'] ?? null;
      $last_name = $data['last_name'] ?? null;
      $gender = $data['gender'] ?? null;
      $avatar = $data['avatar'] ?? null;

      if (!$username) {
        throw new Exception(
          "Username is required",
          422,
          new Exception("username")
        );
      }
      if (!$password) {
        throw new Exception(
          "Password is required",
          422,
          new Exception("password")
        );
      }
      if (!$first_name) {
        throw new Exception(
          "First name is required",
          422,
          new Exception("first_name")
        );
      }
      if (!$last_name) {
        throw new Exception(
          "Last name is required",
          422,
          new Exception("last_name")
        );
      }
      if (!$gender) {
        throw new Exception(
          'Gender is required',
          422,
          new Exception('gender')
        );
      }

      if (!in_array($gender, ['male', 'female'], true)) {
        throw new Exception("Gender must be either 'male' or 'female' (case-sensitive).", 422, new Exception("gender"));
      }

      // Check if the username already in used
      $existingUser = $this->userRepository->getUserByUsername($username);

      if ($existingUser) {
        throw new Exception(
          "Username is already in used.",
          409,
          new Exception("username")
        );
      }

      // TODO: Upload User Avatar to ../../storage/avatars/ folder and get the URL

      $user = $this->userRepository->createUser(
        $username,
        password_hash($password, PASSWORD_BCRYPT),
        $first_name,
        $last_name,
        $gender,
        $avatar
      );

      return $this->response([
        'error' => false,
        'data' => [
          'user' => $user
        ],
        'message' => "User created successfully."
      ], 201);
    } catch (Exception $e) {
      return $this->handleException($e);
    }
  }

  public function verifyToken()
  {
    try {
      $headers = getallheaders();
      $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

      // Extract the token
      if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        throw new Exception("Authorization token not found", 401);
      }

      $token = $matches[1];

      $decoded = $this->jwtHelper->validate($token, $this->jwtHelper->getSecretKey());

      $user = $this->userRepository->getUserById($decoded->sub);

      if (!$user) {
        throw new Exception('User not found', 404);
      }

      $newToken = $this->jwtHelper->generateToken($user['user_id']);

      $safeUserData = $this->sanitizeUserData($user);

      return $this->response([
        'error' => false,
        'data' => [
          'user' => $safeUserData,
          'token' => $newToken
        ],
        'message' => "Token verified and login successfully."
      ], 200);
    } catch (Exception $e) {
      return $this->handleException($e);
    }
  }
}
