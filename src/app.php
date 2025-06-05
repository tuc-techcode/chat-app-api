<?php
if ($route === 'api/auth/login') {
  require_once __DIR__ . '/./routes/auth/login.php';
} else if ($route === 'api/auth/register') {
  require_once __DIR__ . '/./routes/auth/register.php';
} else if ($route === 'api/image') {
  require_once __DIR__ . '/./routes/file/image.php';
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
  } else if ($route === 'api/user/push-token') {
    require_once __DIR__ . '/./routes/user/push-token.php';
  } else if ($route === 'api/pusher/auth') {
    require_once __DIR__ . '/./routes/pusher/pusher.php';
  } else if ($route === 'api/pusher/trigger') {
    require_once __DIR__ . '/./routes/pusher/pusher-trigger.php';
  } else if ($route === 'api/pusher/trigger') {
    require_once __DIR__ . '/./routes/pusher/pusher.php';
  } else if ($route === 'api/user') {
    require_once __DIR__ . '/./routes/user/user.php';
  } else if ($route === 'api/user/search') {
    require_once __DIR__ . '/./routes/user/user-search.php';
  } else if ($route === 'api/user/update') {
    require_once __DIR__ . '/./routes/user/user-update.php';
  } else if ($route === 'api/user/contacts') {
    require_once __DIR__ . '/./routes/user/user-contacts.php';
  } else if ($route === 'api/user/conversations') {
    require_once __DIR__ . '/./routes/conversations/conversation-user.php';
  } else if ($route === 'api/conversations') {
    require_once __DIR__ . '/./routes/conversations/conversations.php';
  } else if ($route === 'api/conversation/messages') {
    require_once __DIR__ . '/./routes/conversations/conversation-messages.php';
  } else if ($route === 'api/conversation/user-messages') {
    require_once __DIR__ . '/./routes/conversations/conversation-direct.php';
  } else if (preg_match('#^api/conversation/(\d+)$#', $route, $matches)) {
    global $paramId;
    $paramId = $matches[1];
    require_once __DIR__ . '/./routes/conversations/conversation.php';
  } else if ($route === 'api/contact') {
    require_once __DIR__ . '/./routes/contacts/contact.php';
  } else if ($route === 'api/contact/search') {
    require_once __DIR__ . '/./routes/contacts/contact-search.php';
  } else if ($route === 'api/message') {
    require_once __DIR__ . '/./routes/message/message.php';
  } else if ($route === 'api/request-approval') {
    require_once __DIR__ . '/./routes/request-approval/request-approval.php';
  } else if ($route === 'api/cancel-request-approval') {
    require_once __DIR__ . '/./routes/request-approval/cancel-request-approval.php';
  } else if ($route === 'api/pending-request-approval') {
    require_once __DIR__ . '/./routes/request-approval/pending-request-approval.php';
  } else if ($route === 'api/my-pending-request') {
    require_once __DIR__ . '/./routes/request-approval/my-pending-request.php';
  } else if ($route === 'api/my-completed-request') {
    require_once __DIR__ . '/./routes/request-approval/my-completed-request.php';
  } else {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'The requested route was not found.']);
    exit();
  }
}
