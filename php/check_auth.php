<?php
session_start();

if (!isset($_SESSION['admin']) || (time() - $_SESSION['time']) > 300) {
    http_response_code(401);
    exit("Не авторизован");
}