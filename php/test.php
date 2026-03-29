<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo json_encode(['ok' => true, 'php_version' => phpversion(), 'session_path' => session_save_path()]);
?>