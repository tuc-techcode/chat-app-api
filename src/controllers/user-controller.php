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

  public function getUserContacts($user_id)
  {
    try {
      $searchTerm = $_GET['searchTerm'] ?? '';
      // var_dump($user_id);

      $results = $this->userRepository->searchContacts($user_id, $searchTerm);

      return $this->response([
        'error' => false,
        'data' => $results,
        'message' => "Search successfully."
      ], 200);
    } catch (Exception $e) {
      return $this->handleException($e);
    }
  }
}
