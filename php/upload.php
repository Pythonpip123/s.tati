<?php
declare(strict_types=1);

/**
 * upload.php — Загрузка изображений (PHP 8.4)
 * Формат имён: {g|s}_000001.ext
 * Метаданные: data/gallery.json / data/shop.json
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/upload_errors.log');

// Создаём папку для логов
$logDir = dirname((string) ini_get('error_log'));
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Старт сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => false,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
        'lazy_write' => true,
    ]);
}

header('Content-Type: application/json; charset=utf-8');

// Конфигурация
$config = [
    'max_file_size' => 10 * 1024 * 1024, // 10 MB
    'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    'max_id' => 999_999,
    'targets' => ['gallery', 'shop'],
];

// Проверка авторизации
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    $timeout = 300;
    $now = time();
    if (($now - ($_SESSION['auth_time'] ?? 0)) > $timeout) {
        sendJson(['error' => 'Сессия истекла'], 401);
    }
    sendJson(['error' => 'Доступ запрещён'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['error' => 'Method not allowed'], 405);
}

try {
    // Получение данных (поддержка JSON + form)
    $data = !empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true, flags: JSON_THROW_ON_ERROR);

    $target = $data['target'] ?? '';
    $name = trim($data['name'] ?? '');
    $price = $data['price'] ?? null;

    // Валидация target
    if (!in_array($target, $config['targets'], strict: true)) {
        sendJson(['error' => 'Неверный тип загрузки'], 400);
    }

    // Валидация названия
    if ($name === '' || mb_strlen($name) > 100) {
        sendJson(['error' => 'Неверное название'], 400);
    }

    // Валидация цены (только для shop)
    if ($target === 'shop') {
        if ($price === null || !is_numeric($price) || (float) $price < 0) {
            sendJson(['error' => 'Неверная цена'], 400);
        }
    }

    // Обработка файла
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Файл слишком большой',
            UPLOAD_ERR_FORM_SIZE => 'Файл слишком большой',
            UPLOAD_ERR_PARTIAL => 'Файл загружен не полностью',
            UPLOAD_ERR_NO_FILE => 'Файл не выбран',
        ];
        $code = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
        sendJson(['error' => $errors[$code] ?? 'Ошибка загрузки'], 400);
    }

    $file = $_FILES['image'];

    // Проверка размера
    if ($file['size'] > $config['max_file_size']) {
        sendJson(['error' => 'Файл превышает лимит 10 МБ'], 400);
    }

    // Проверка MIME-типа
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $config['allowed_mime_types'], strict: true)) {
        sendJson(['error' => 'Недопустимый формат файла'], 400);
    }

    // Получение и валидация расширения
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $config['allowed_extensions'], strict: true)) {
        sendJson(['error' => 'Недопустимое расширение файла'], 400);
    }

    // Нормализация расширения
    $ext = match ($ext) {
        'jpg' => 'jpeg',
        default => $ext,
    };

    // Генерация уникального имени
    $prefix = $target === 'shop' ? 's' : 'g';
    $uploadDir = __DIR__ . "/../{$target}";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Поиск следующего ID
    $nextId = findNextId($uploadDir, $prefix, $ext, $config['max_id']);

    $filename = sprintf('%s_%06d.%s', $prefix, $nextId, $ext);
    $destination = "{$uploadDir}/{$filename}";

    // Перемещение файла
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        sendJson(['error' => 'Не удалось сохранить файл'], 500);
    }

    chmod($destination, 0644);

    // Сохранение метаданных
    $metadata = saveMetadata($target, [
        'id' => $nextId,
        'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        'filename' => $filename,
        'path' => "/{$target}/{$filename}",
        'uploaded_at' => date('Y-m-d H:i:s'),
        'price' => $target === 'shop' ? (float) $price : null,
    ]);

    // Ответ
    sendJson([
        'success' => true,
        'message' => 'Файл успешно загружен',
        'data' => [
            'id' => $nextId,
            'filename' => $filename,
            'url' => $metadata['path'],
            'name' => $metadata['name'],
            'price' => $metadata['price'] ?? null,
        ],
    ]);

} catch (Throwable $e) {
    error_log(sprintf(
        "[%s] Exception in %s:%d — %s",
        date('Y-m-d H:i:s'),
        $e->getFile(),
        $e->getLine(),
        $e->getMessage()
    ));
    sendJson(['error' => 'Internal server error', 'debug' => $e->getMessage()], 500);
}

// === Вспомогательные функции ===

/**
 * Находит следующий доступный ID для файла
 */
function findNextId(string $dir, string $prefix, string $ext, int $maxId): int
{
    $pattern = "{$dir}/{$prefix}_*.{$ext}";
    $files = glob($pattern) ?: [];

    $maxNum = 0;
    foreach ($files as $file) {
        if (preg_match("/{$prefix}_(\d{6})\./", basename($file), $matches)) {
            $num = (int) $matches[1];
            $maxNum = max($maxNum, $num);
        }
    }

    $nextId = $maxNum + 1;
    if ($nextId > $maxId) {
        throw new RuntimeException("Достигнут лимит файлов ({$maxId})");
    }

    return $nextId;
}

/**
 * Сохраняет метаданные в data/{target}.json
 */
function saveMetadata(string $target, array $metadata): array
{
    $metaDir = __DIR__ . '/../data';
    $metaFile = "{$metaDir}/{$target}.json";

    if (!is_dir($metaDir)) {
        mkdir($metaDir, 0755, true);
    }

    // Читаем существующие данные
    $items = [];
    if (file_exists($metaFile)) {
        $content = file_get_contents($metaFile);
        if ($content !== false && $content !== '') {
            $decoded = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        }
    }

    // Добавляем новый элемент (без цены для галереи)
    $safe = [
        'id' => $metadata['id'],
        'name' => $metadata['name'],
        'filename' => $metadata['filename'],
        'path' => $metadata['path'],
        'uploaded_at' => $metadata['uploaded_at'],
    ];

    if ($target === 'shop' && isset($metadata['price'])) {
        $safe['price'] = $metadata['price'];
    }

    $items[] = $safe;

    // Сохраняем
    $json = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (file_put_contents($metaFile, $json) === false) {
        throw new RuntimeException("Не удалось записать файл метаданных");
    }

    return $safe;
}

/**
 * Отправка JSON-ответа
 */
function sendJson(array $data, int $httpCode = 200): never
{
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}