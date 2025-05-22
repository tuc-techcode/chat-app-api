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

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $socketId = isset($data['socketId']) ? trim($data['socketId']) : null;
            $channelName = isset($data['channelName']) ? trim($data['channelName']) : null;

            if (!$socketId) {
                throw new Exception("Socket ID is required", 400);
            }

            if (!$channelName) {
                throw new Exception("Channel Name is required", 400);
            }

            $result = $this->pusherService->authenticate($socketId, $channelName);
            // if (!$result) {
            //     throw new Exception("Authentication failed", 401);
            // }

            return $this->response([
                'error' => false,
                'data' => $result,
                'message' => 'Authenticated successfully'
            ], 201);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function trigger()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $channel = isset($data['channel']) ? trim($data['channel']) : null;
            $event = isset($data['event']) ? trim($data['event']) : null;
            $data = isset($data['data']) ? $data['data'] : null;

            $result = $this->pusherService->trigger($channel, $event, $data);

            return $this->response([
                'error' => false,
                'data' => $result,
                'message' => 'Event triggered successfully'
            ], 201);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
