<?php
// Get the requested URI and remove the base path
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/chat-app-api';
$path = str_replace($basePath, '', $requestUri);
$path = trim($path, '/');

$route = empty($path) ? 'dashboard' : $path;
$request_method = $_SERVER['REQUEST_METHOD'];

if (strpos($route, 'api/') === 0) {
  require_once __DIR__ . '/vendor/autoload.php';
  require_once __DIR__ . '/src/app.php';
} else {
  http_response_code(404);
  header('Content-Type: application/json');
  echo json_encode([
    "error" => true,
    "status" => 404,
    "message" => "API endpoint not found.",
  ]);
}
