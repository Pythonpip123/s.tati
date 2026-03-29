<?php
require "check_auth.php";

$file = $_FILES['file'];
$title = $_POST['title'];
$price = $_POST['price'];

$dir = "../shop/";
$files = scandir($dir);

$count = 1;
foreach ($files as $f) {
    if (preg_match('/s_(\d+)/', $f, $m)) {
        $count = max($count, $m[1] + 1);
    }
}

$name = "s_" . $count . ".jpg";
move_uploaded_file($file['tmp_name'], $dir . $name);

$dataFile = "../data/shop.json";
$data = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];

$data[] = [
    "image" => "/shop/" . $name,
    "title" => $title,
    "price" => $price
];

file_put_contents($dataFile, json_encode($data));

echo "ok";