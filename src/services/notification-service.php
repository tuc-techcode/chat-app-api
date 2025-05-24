<?php

class Expo_Notification_Service
{
  private $expo;

  public function __construct()
  {
    $this->expo = \ExponentPhpSDK\Expo::normalSetup();
  }


  public function sendPushNotification(array $tokens, string $channel, string $title, string $message)
  {
    $notification = [
      'title' => $title,
      'body' => $message,
      'data' => json_encode(array('someData' => 'goes here'))
    ];

    try {
      // $notification = ['body' => 'Hello World!', 'data' => json_encode(array('someData' => 'goes here'))];

      $this->expo->subscribe($channel, $tokens[0]);

      $this->expo->notify([$channel], $notification);
    } catch (Exception $e) {
      error_log('Push notification error: ' . $e->getMessage());
    }
  }
}
