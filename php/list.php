<?php
header('Content-Type: application/json');

$files = array_diff(scandir(__DIR__ . '../gallery'), array('.', '..'));

$result = [];

foreach ($files as $file) {
    $result[] = "../gallery/" . $file;
}

echo json_encode($result);