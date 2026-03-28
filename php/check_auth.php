<?php
header('Content-Type: application/json; charset=utf-8');

session_start();

if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden', 'auth' => false]);
    exit;
}

if (time() - $_SESSION['auth_time'] > 300) {
    session_destroy();
    http_response_code(401);
    echo json_encode(['error' => 'Session expired', 'auth' => false]);
    exit;
}

$_SESSION['auth_time'] = time();
echo json_encode(['auth' => true, 'success' => true]);
?>