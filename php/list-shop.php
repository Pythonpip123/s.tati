<?php

$dataFile = "../shop-data.json";

if (!file_exists($dataFile)) {
    echo json_encode([]);
    exit;
}

$json = file_get_contents($dataFile);
echo $json;