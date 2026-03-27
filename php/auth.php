<?php
session_start();

$secret = "12345";

// LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if ($password === $secret) {
        $_SESSION['admin'] = true;
        $_SESSION['time'] = time();

        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["error" => "Неверный пароль"]);
    }
}

// CHECK
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_SESSION['admin']) && $_SESSION['admin']) {

        // 5 минут
        if (time() - $_SESSION['time'] > 300) {
            session_destroy();
            echo json_encode(["auth" => false]);
            exit;
        }

        echo json_encode(["auth" => true]);
    } else {
        echo json_encode(["auth" => false]);
    }
}