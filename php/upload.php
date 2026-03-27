<?php
$uploadDir = __DIR__ . '../gallery/';

$secret = "12345"; // придумай пароль

if ($_POST['password'] !== $secret) {
    echo json_encode(["error" => "Нет доступа"]);
    exit;
}

if ($_FILES['file']) {
    $file = $_FILES['file'];

    if ($file['type'] !== 'image/png') {
        echo json_encode(["error" => "Только PNG"]);
        exit;
    }
    $title = $_POST['title'] ?? '';
    $fileName = time() . "_" . basename($file['name']);
    $targetFile = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        echo json_encode([
            "success" => true,
            "title" => $title,
            "url" => "../gallery/" . $fileName
        ]);
    } else {
        echo json_encode(["error" => "Ошибка загрузки"]);
    }
}