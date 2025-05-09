<?php
require_once __DIR__ . '/./base-controller.php';
require_once __DIR__ . '/../services/pusher-service.php';

class Pusher_Controller extends Base_Controller
{
    private $pusherService;

    public function __construct()
    {
        $this->pusherService = new Pusher_Service();
    }

    public function auth()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        // Get socket ID and channel name from POST request
        $socketId = isset($data['socket_id']) ? trim($data['socket_id']) : null;
        $channel = isset($data['channel_name']) ? trim($data['channel_name']) : null;

        if (!$socketId || !$channel) {
            throw new Exception("Socket ID and channel name are required", 400);
        }

        // Here you should add your authentication logic
        // For example, check if the user is logged in and has access to the channel

        try {
            $auth = $this->pusherService->authenticate($channel, $socketId);
            return json_encode($auth);
        } catch (\Exception $e) {
            return $this->handleException($e);
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
            return $this->handleException($e);
        }
    }
}
