<?php
header('Content-Type: application/json');

$response = [
    'status' => 'success',
    'message' => 'Authorization successful'
];

echo json_encode($response);
?>