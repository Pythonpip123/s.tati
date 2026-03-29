<?php
header('Content-Type: application/json; charset=utf-8');

// Проверка авторизации
session_start();

if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden', 'auth' => false]);
    exit;
}

if (time() - $_SESSION['auth_time'] > 300) {
    session_destroy();
    http_response_code(401);
    echo json_encode(['error' => 'Session expired', 'auth' => false]);
    exit;
}

// Обновляем время активности
$_SESSION['auth_time'] = time();

$method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$dataDir = "../data";

if ($method === 'GET') {
    // Получить список картин из обоих файлов с добавлением target
    $items = [];

    // Загружаем галерею
    $galleryFile = "$dataDir/gallery.json";
    if (file_exists($galleryFile)) {
        $gallery = json_decode(file_get_contents($galleryFile), true) ?? [];
        foreach ($gallery as $item) {
            $item['target'] = 'gallery';
            $items[] = $item;
        }
    }

    // Загружаем магазин
    $shopFile = "$dataDir/shop.json";
    if (file_exists($shopFile)) {
        $shop = json_decode(file_get_contents($shopFile), true) ?? [];
        foreach ($shop as $item) {
            $item['target'] = 'shop';
            $items[] = $item;
        }
    }

    echo json_encode($items);

} elseif ($method === 'DELETE') {
    // Удалить картину
    $data = json_decode(file_get_contents('php://input'), true);
    $itemId = $data['id'] ?? null;
    $target = $data['target'] ?? null;

    if (!$itemId || !in_array($target, ['gallery', 'shop'], true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing id or target']);
        exit;
    }

    $metaFile = "$dataDir/{$target}.json";
    $deletedItem = null;

    if (file_exists($metaFile)) {
        $items = json_decode(file_get_contents($metaFile), true) ?? [];
        $filtered = [];

        foreach ($items as $item) {
            if ((int) ($item['id'] ?? 0) === (int) $itemId && $deletedItem === null) {
                $deletedItem = $item;
                continue;
            }

            $filtered[] = $item;
        }

        if ($deletedItem !== null) {
            file_put_contents($metaFile, json_encode(array_values($filtered), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $filename = $deletedItem['filename'] ?? '';
            if ($filename !== '') {
                $filePath = __DIR__ . "/../{$target}/{$filename}";
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
            exit;
        }
    }

    http_response_code(404);
    echo json_encode(['error' => 'Item not found']);
} else {

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>