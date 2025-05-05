<?php
require_once __DIR__ . '/../services/pusher-service.php';

class PusherController
{
    private $pusherService;

    public function __construct()
    {
        $this->pusherService = new PusherService();
    }

    public function auth()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        // Get socket ID and channel name from POST request
        $socketId = isset($data['socket_id']) ? trim($data['socket_id']) : null;
        $channel = isset($data['channel_name']) ? trim($data['channel_name']) : null;

        // var_dump($socketId);
        // var_dump($channel);

        if (!$socketId || !$channel) {
            http_response_code(400);
            return json_encode([
                'error' => true,
                'message' => 'Socket ID and channel name are required'
            ]);
        }

        // Here you should add your authentication logic
        // For example, check if the user is logged in and has access to the channel

        try {
            $auth = $this->pusherService->authenticate($channel, $socketId);
            return json_encode($auth);
        } catch (\Exception $e) {
            http_response_code(403);
            return json_encode([
                'error' => true,
                'message' => 'Authentication failed'
            ]);
        }
    }

    public function trigger($channel, $event, $data)
    {
        try {
            $result = $this->pusherService->trigger($channel, $event, $data);
            return json_encode([
                'error' => false,
                'message' => 'Event triggered successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            return json_encode([
                'error' => true,
                'message' => 'Failed to trigger event'
            ]);
        }
    }
}
