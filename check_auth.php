<?php

header('Content-Type: application/json');

// Simulate some conditions
$session_active = false; // Change this to true to simulate an active session
$error_occurred = true; // Set to false to simulate no errors

if (!$session_active) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired']);
    http_response_code(401);
    exit();
}

if ($error_occurred) {
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    http_response_code(403);
    exit();
}

// Normal response if everything checks out
echo json_encode(['status' => 'success', 'data' => 'Authenticated successfully']);

?>