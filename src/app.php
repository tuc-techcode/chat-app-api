<?php
if ($route === 'api/auth/login') {
  require_once __DIR__ . '/./routes/auth/login.php';
} else if ($route === 'api/auth/register') {
  require_once __DIR__ . '/./routes/auth/register.php';
} else {
  require_once __DIR__ . '/./middlewares/auth-middleware.php';

  $authMiddleware = new AuthMiddleware();

  if (!$authMiddleware->handle()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Unauthorized.']);
    exit();
  }

  if ($route === 'api/auth/verify') {
    require_once __DIR__ . '/./routes/auth/verify.php';
  } else if ($route === 'api/pusher/auth' || $route === 'api/pusher/trigger') {
    require_once __DIR__ . '/./routes/pusher.php';
  } else if ($route === 'api/conversations') {
    require_once __DIR__ . '/./routes/conversations/conversations.php';
  } else if ($route === 'api/conversation-messages') {
    require_once __DIR__ . '/./routes/conversations/conversation-messages.php';
  } else if ($route === 'api/conversation-details') {
    require_once __DIR__ . '/./routes/conversations/conversation-details.php';
  } else if ($route === 'api/contact') {
    require_once __DIR__ . '/./routes/contacts/contact.php';
  } else if ($route === 'api/user/search') {
    require_once __DIR__ . '/./routes/users/users.php';
  } else {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'The requested route was not found.']);
    exit();
  }
}
