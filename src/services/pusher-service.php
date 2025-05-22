<?php

use Pusher\Pusher;

class Pusher_Service
{
    private $pusher;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/pusher.php';

        $this->pusher = new Pusher(
            $config['app_key'],
            $config['app_secret'],
            $config['app_id'],
            [
                'cluster' => $config['cluster'],
                'useTLS' => $config['useTLS'],
                'encrypted' => true
            ]
        );
    }

    public function trigger(string $channel, string $event, array $data): bool
    {
        try {
            $this->pusher->trigger($channel, $event, $data);
            return true;
        } catch (\Pusher\PusherException $e) {
            error_log('Pusher trigger error: ' . $e->getMessage());
            return false;
        }
    }

    public function authenticate(string $socketId, string $channelName)
    {
        try {
            // For private channels
            if (strpos($channelName, 'private-') === 0) {
                $auth = $this->pusher->authorizeChannel($channelName, $socketId);
                return $auth;
            }

            // For presence channels
            if (strpos($channelName, 'presence-') === 0) {
                $userData = [
                    'user_id' => '1',
                    'user_info' => ['name' => 'Jake Rosales']
                ];

                $auth = $this->pusher->authorizePresenceChannel(
                    $channelName,
                    $socketId,
                    json_encode($userData)
                );
                return $auth;
            }

            throw new Exception("Invalid channel type", 400);
        } catch (\Exception $e) {
            error_log('Pusher auth error: ' . $e->getMessage());
            throw $e;
        }
    }
}
