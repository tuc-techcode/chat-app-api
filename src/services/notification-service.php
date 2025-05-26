<?php

class Expo_Notification_Service
{
  private $expo;

  public function __construct()
  {
    $this->expo = \ExponentPhpSDK\Expo::normalSetup();
  }


  public function sendPushNotification(array $tokens, string $channel, string $title, string $message, array $data = [])
  {
    $notification = [
      'title' => $title,
      'body' => $message,
      'data' => json_encode($data)
    ];

    try {
      foreach ($tokens as $token) {
        $this->expo->subscribe($channel, $token);
      }

      $this->expo->notify([$channel], $notification);
    } catch (Exception $e) {
      error_log('Push notification error: ' . $e->getMessage());
    }
  }
}
