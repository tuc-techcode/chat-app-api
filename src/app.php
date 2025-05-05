<?php
if ($route === 'api/auth/verify') {
  require_once __DIR__ . '/./routes/auth/verify.php';
} else if ($route === 'api/auth/login') {
  require_once __DIR__ . '/./routes/auth/login.php';
} else if ($route === 'api/auth/register') {
  require_once __DIR__ . '/./routes/auth/register.php';
} else if ($route === 'api/pusher/auth' || $route === 'api/pusher/trigger') {
  require_once __DIR__ . '/./routes/pusher.php';
} else {
  http_response_code(404);
  header('Content-Type: application/json');
  echo json_encode(['error' => true, 'message' => 'The requested route was not found.']);
  exit();
}
