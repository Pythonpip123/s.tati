<?php
session_start();
header('Content-Type: application/json');

$PASSWORD = "12345";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if ($password === $PASSWORD) {
        $_SESSION['admin'] = true;
        $_SESSION['time'] = time();
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_SESSION['admin']) && $_SESSION['admin']) {
        if (time() - $_SESSION['time'] > 300) {
            session_destroy();
            echo json_encode(["auth" => false]);
            exit;
        }
        $_SESSION['time'] = time();
        echo json_encode(["auth" => true]);
    } else {
        echo json_encode(["auth" => false]);
    }
    exit;
}