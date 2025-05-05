<?php

use Pusher\Pusher;

class PusherService
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
                // 'useTLS' => $config['useTLS'],
                // 'encrypted' => true
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

    public function authenticate(string $channelName, string $socketId): string
    {
        try {
            // For private channels
            if (strpos($channelName, 'private-') === 0) {
                return $this->pusher->authorizeChannel($channelName, $socketId);
            }
            // For presence channels
            elseif (strpos($channelName, 'presence-') === 0) {
                $userData = [
                    'user_id' => $_SESSION['user_id'] ?? 'guest_' . uniqid(),
                    'user_info' => [
                        'name' => $_SESSION['username'] ?? 'Guest'
                    ]
                ];

                // Convert array to JSON string
                $userDataString = json_encode($userData);

                return $this->pusher->authorizePresenceChannel(
                    $channelName,
                    $socketId,
                    $userDataString
                );
            }

            throw new \InvalidArgumentException('Invalid channel type');
        } catch (\Exception $e) {
            error_log('Pusher auth error: ' . $e->getMessage());
            throw $e;
        }
    }
}
