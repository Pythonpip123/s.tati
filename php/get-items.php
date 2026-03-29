<?php
declare(strict_types=1);

/**
 * get-items.php — Публичный API для получения списка товаров/изображений
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$target = $_GET['target'] ?? '';

if (!in_array($target, ['gallery', 'shop'], strict: true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Неверный параметр target'], JSON_UNESCAPED_UNICODE);
    exit;
}

$metaFile = __DIR__ . "/../data/{$target}.json";

if (!file_exists($metaFile)) {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $content = file_get_contents($metaFile);
    $items = $content !== false ? json_decode($content, true, flags: JSON_THROW_ON_ERROR) : [];

    // Отдаём только безопасные поля
    $safe = array_map(fn($item) => [
        'id' => $item['id'] ?? null,
        'name' => $item['name'] ?? '',
        'filename' => $item['filename'] ?? '',
        'path' => $item['path'] ?? '',
        'uploaded_at' => $item['uploaded_at'] ?? null,
        ...($target === 'shop' && isset($item['price']) ? ['price' => $item['price']] : []),
    ], $items ?? []);

    echo json_encode($safe, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка чтения данных', 'debug' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}