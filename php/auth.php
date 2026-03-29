<?php
declare(strict_types=1);
$RECAPTCHA_SECRET = getenv('RECAPTCHA_SECRET') ?: '6LeMNp0sAAAAAMaTZ0kDTNE4_u-sT10HwpeEtnE7';

/**
 * auth.php — Админ-аутентификация (совместим с JSON и form-encoded)
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/auth_errors.log');

// Создаём папку для логов
$logDir = dirname((string) ini_get('error_log'));
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

// Старт сессии (без deprecated-параметров для PHP 8.4)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '0');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.lazy_write', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// 🔑 ПАРОЛЬ — ЗАМЕНИ НА СВОЙ!
$ADMIN_PASSWORD = getenv('ADMIN_PASS') ?: 'Pidor123';

function verifyCaptcha(string $token): bool
{
    global $RECAPTCHA_SECRET;

    $url = 'https://www.google.com/recaptcha/api/siteverify';

    $data = [
        'secret' => $RECAPTCHA_SECRET,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if (!$result)
        return false;

    $json = json_decode($result, true);

    return $json['success'] ?? false;
}

/**
 * Отправка JSON-ответа
 */
function sendJson(array $data, int $httpCode = 200): void
{
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Получение данных: поддерживает JSON и form-urlencoded
 */
function getInputData(): array
{
    // Если есть $_POST — используем его (form-encoded)
    if (!empty($_POST)) {
        return $_POST;
    }

    // Пробуем прочитать сырой ввод
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return [];
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

    // Если JSON — парсим
    if (str_contains($contentType, 'application/json')) {
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    // Если form-urlencoded — парсим через parse_str
    parse_str($raw, $parsed);
    return $parsed;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';

// === Обработка запросов ===
if ($method === 'POST') {
    // ВХОД
    $data = getInputData();
    $password = isset($data['password']) ? trim((string) $data['password']) : '';

    if ($password === '') {
        sendJson(['success' => false, 'error' => 'Пароль не указан'], 400);
    }

    if (hash_equals($ADMIN_PASSWORD, $password)) {
        $_SESSION['admin'] = true;
        $_SESSION['auth_time'] = time();
        $_SESSION['last_activity'] = time();
        sendJson(['success' => true, 'message' => 'Авторизация успешна']);
    }

    sendJson(['success' => false, 'error' => 'Неверный пароль'], 401);

} elseif ($method === 'GET') {
    // ПРОВЕРКА АВТОРИЗАЦИИ
    $isAdmin = $_SESSION['admin'] ?? false;

    if (!$isAdmin) {
        sendJson(['auth' => false, 'error' => 'Неавторизован'], 403);
    }

    $timeout = 300;
    $now = time();
    $authTime = $_SESSION['auth_time'] ?? 0;

    if (($now - $authTime) > $timeout) {
        session_unset();
        session_destroy();
        sendJson(['auth' => false, 'error' => 'Сессия истекла'], 401);
    }

    $_SESSION['last_activity'] = $now;
    sendJson([
        'auth' => true,
        'expires_in' => max(0, $timeout - ($now - $authTime)),
    ]);

} elseif ($method === 'DELETE') {
    // ВЫХОД
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'domain' => $params['domain'] ?? '',
                'secure' => $params['secure'],
                'httponly' => true,
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }
    session_unset();
    session_destroy();
    sendJson(['success' => true, 'message' => 'Выход выполнен']);

} else {
    sendJson(['error' => 'Method not allowed', 'allowed' => ['GET', 'POST', 'DELETE']], 405);
}