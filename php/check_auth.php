<?php
session_start();

if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    http_response_code(403);
    exit("Forbidden");
}

if (time() - $_SESSION['time'] > 300) {
    session_destroy();
    http_response_code(401);
    exit("Session expired");
}

$_SESSION['time'] = time();