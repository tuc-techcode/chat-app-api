<?php

class Base_Controller
{
  protected function response(array $data, int $statusCode = 200)
  {
    http_response_code($statusCode);
    return $data;
  }

  protected function handleException(Exception $e)
  {
    $statusCode = (is_numeric($e->getCode()) && $e->getCode() > 0) ? (int)$e->getCode() : 500;
    http_response_code($statusCode);
    $errorData = [
      'error'   => true,
      'message' => $e->getMessage()
    ];
    if ($statusCode > 404 && $statusCode < 500) {
      $errorData['error_field'] = $e->getPrevious() ? $e->getPrevious()->getMessage() : null;
    }
    return $errorData;
  }

  protected function sanitizeUserData(array $user): array
  {
    $sensitiveFields = ['password_hash'];

    foreach ($sensitiveFields as $field) {
      if (array_key_exists($field, $user)) {
        unset($user[$field]);
      }
    }

    return $user;
  }
}
