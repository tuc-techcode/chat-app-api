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

  public function addContact($user_id)
  {
    try {
      $data = json_decode(file_get_contents('php://input'), true);

      $contact_id = isset($data['contactId']) ? trim($data['contactId']) : null;

      if (!$user_id) {
        throw new Exception(
          "User ID is required",
          422,
          new Exception("userId")
        );
      }

      if (!$contact_id) {
        throw new Exception(
          "Contact ID is required",
          422,
          new Exception("contact_id")
        );
      }

      $isContact = $this->contactRepository->isContact($user_id, $contact_id);

      if ($isContact) {
        throw new Exception(
          "User is already added as contact required",
          422,
          new Exception("contact_id")
        );
      }

      $isAdded = $this->contactRepository->addContact($user_id, $contact_id);

      if (!$isAdded) {
        throw new Exception(
          "An error has occured adding user to contact.",
          400
        );
      }

      return $this->response([
        'error' => false,
        'data' => $isAdded,
        'message' => "User successfully added to contact."
      ], 200);
    } catch (Exception $e) {
      return $this->handleException($e);
    }
  }

  public function searchUserContacts($user_id)
  {
    try {
      $searchTerm = $_GET['search'] ?? '';

      $results = $this->contactRepository->searchAllUsers($user_id, $searchTerm);

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
