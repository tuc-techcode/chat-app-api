<?php
require_once __DIR__ . '/../controllers/pusher-controller.php';

$pusherController = new Pusher_Controller();

// Pusher authentication endpoint
if ($route === 'api/pusher/auth' && $request_method === 'POST') {
    header('Content-Type: application/json');
    echo $pusherController->auth();
    exit;
}

// Pusher trigger event endpoint (you may want to secure this endpoint)
if ($route === 'api/pusher/trigger' && $request_method === 'POST') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['channel']) || !isset($data['event']) || !isset($data['data'])) {
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'message' => 'Channel, event, and data are required'
        ]);
        exit;
    }

    echo $pusherController->trigger($data['channel'], $data['event'], $data['data']);
    exit;
}
