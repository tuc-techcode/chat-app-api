<?php
require_once __DIR__ . '/./base-controller.php';
require_once __DIR__ . '/../repositories/contact-repository.php';

class Contact_Controller extends Base_Controller
{
  private $contactRepository;

  public function __construct()
  {
    $this->contactRepository = new Contact_Repository();
  }

  public function getUserContacts($user_id)
  {
    try {
      $contacts = $this->contactRepository->getUserContacts($user_id);

      return $this->response([
        'error' => false,
        'data' => $contacts,
        'message' => "Contacts fetch successfully."
      ], 200);
    } catch (Exception $e) {
      return $this->handleException($e);
    }
  }
}
