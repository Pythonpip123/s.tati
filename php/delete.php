<?php
require "check_auth.php";

$image = $_POST['image'];

$file = "../data/gallery.json";
$data = json_decode(file_get_contents($file), true);

$data = array_filter($data, fn($item) => $item['image'] !== $image);

file_put_contents($file, json_encode(array_values($data)));

echo "ok";