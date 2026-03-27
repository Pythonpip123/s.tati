<?php
session_start();

$password = $_POST['password'];

if ($password === "12345") {
    $_SESSION['admin'] = true;
    $_SESSION['time'] = time();
    echo "ok";
} else {
    http_response_code(403);
}