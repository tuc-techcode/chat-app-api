<?php
require_once __DIR__ . '/./base-controller.php';
require_once __DIR__ . '/../repositories/user-repository.php';

class User_Controller extends Base_Controller
{
  private $userRepository;

  public function __construct()
  {
    $this->userRepository = new User_Repository();
  }

  public function updateUserPushToken($userId)
  {
    try {
      $this->userRepository->startTransaction();
      $data = json_decode(file_get_contents('php://input'), true);

      $pushToken = $data['pushToken'] ?? null;

      if (!$pushToken) {
        throw new Exception("Push token is required to enable push notification.", 400);
      }

      // Validate Expo push token format
      if (!preg_match('/^ExponentPushToken\[.+\]$/', $pushToken)) {
        throw new Exception("Invalid Expo push token format", 400);
      }

      // clear token from any other users
      $this->userRepository->clearDuplicatePushTokens($pushToken);

      $success  = $this->userRepository->updateUserPushToken($userId, $pushToken);

      if (!$success) {
        throw new Exception("Failed to save push token.", 500);
      }

      $this->userRepository->commitTransaction();

      return $this->response([
        'error' => false,
        'data' => $success,
        'message' => "Push token saved successfully."
      ]);
    } catch (Exception $e) {
      $this->userRepository->rollbackTransaction();
      return $this->handleException($e);
    }
  }
}
