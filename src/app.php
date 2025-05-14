<?php
if ($route === 'api/auth/verify') {
  require_once __DIR__ . '/./routes/auth/verify.php';
} else if ($route === 'api/auth/login') {
  require_once __DIR__ . '/./routes/auth/login.php';
} else if ($route === 'api/auth/register') {
  require_once __DIR__ . '/./routes/auth/register.php';
} else if ($route === 'api/pusher/auth' || $route === 'api/pusher/trigger') {
  require_once __DIR__ . '/./routes/pusher.php';
} else if (
  preg_match(
    '#^api/conversation/user/([A-Za-z0-9]+)$#',
    $route,
    $matches
  )
) {
  global $userId;
  $paramUserId = $matches[1];
  require_once __DIR__ . '/./routes/conversations/conversation.php';
} else if (
  preg_match(
    '#^api/contact/user/([A-Za-z0-9]+)$#',
    $route,
    $matches
  )
) {
  global $userId;
  $paramUserId = $matches[1];
  require_once __DIR__ . '/./routes/contact/contact.php';
} else {
  http_response_code(404);
  header('Content-Type: application/json');
  echo json_encode(['error' => true, 'message' => 'The requested route was not found.']);
  exit();
}
