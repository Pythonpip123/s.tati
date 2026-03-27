<?php

$uploadDir = "../shop/";
$dataFile = "../shop-data.json";

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$file = $_FILES['file'];
$title = $_POST['title'] ?? '';
$price = $_POST['price'] ?? '';

$fileName = time() . "_" . basename($file['name']);
$targetFile = $uploadDir . $fileName;

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($_FILES['file']['type'], $allowedTypes)) {
    die("Invalid file type");
}

move_uploaded_file($file['tmp_name'], $targetFile);

$url = $targetFile;

// читаем JSON
$data = [];

if (file_exists($dataFile)) {
    $json = file_get_contents($dataFile);
    $data = json_decode($json, true) ?? [];
}

// добавляем новую запись
$data[] = [
    "url" => $url,
    "title" => $title,
    "price" => $price
];

// сохраняем
file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));

// ответ
echo json_encode(end($data));