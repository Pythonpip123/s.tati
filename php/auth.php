<?php
declare(strict_types=1);

/**
 * auth.php — Админ-аутентификация (PHP 8.4)
 * Поддерживает: application/json И application/x-www-form-urlencoded
 */

// Настройки ошибок
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/auth_errors.log');

// Создаём папку для логов
$logDir = dirname((string) ini_get('error_log'));
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Старт сессии с современными настройками
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 0,
        'cookie_httponly' => true,
        'cookie_secure' => false, // true если только HTTPS
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
        'lazy_write' => true,
        'sid_length' => 32,
        'sid_bits_per_character' => 5,
    ]);
}

header('Content-Type: application/json; charset=utf-8');

// Пароль администратора (замени на свой!)
$ADMIN_PASSWORD = getenv('ADMIN_PASS') ?: 'твой_секретный_пароль_123';

/**
 * Отправка JSON-ответа
 */
function sendJson(array $data, int $httpCode = 200): never
{
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}

/**
 * Получение входных данных (поддержка JSON + form-encoded)
 */
function getInputData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

    // Если пришли POST-данные (form-encoded) — используем их
    if (!empty($_POST)) {
        return $_POST;
    }

    // Если JSON — парсим
    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }
        return json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
    }

    // fallback: пробуем распарсить как parse_str
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        parse_str($raw, $parsed);
        return $parsed;
    }

    return [];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';

try {
    match ($method) {
        'POST' => handleLogin(),
        'GET' => handleCheckAuth(),
        'DELETE' => handleLogout(),
        default => sendJson(['error' => 'Method not allowed', 'allowed' => ['GET', 'POST', 'DELETE']], 405),
    };
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

// === Обработчики ===

function handleLogin(): void
{
    global $ADMIN_PASSWORD;

    $data = getInputData();
    $password = trim($data['password'] ?? '');

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
}

function handleCheckAuth(): void
{
    $isAdmin = $_SESSION['admin'] ?? false;

    if (!$isAdmin) {
        sendJson(['auth' => false, 'error' => 'Неавторизован'], 403);
    }

    // Проверка таймаута (5 минут)
    $timeout = 300;
    $now = time();
    $authTime = $_SESSION['auth_time'] ?? 0;

    if (($now - $authTime) > $timeout) {
        session_unset();
        session_destroy();
        sendJson(['auth' => false, 'error' => 'Сессия истекла'], 401);
    }

    // Обновляем время активности
    $_SESSION['last_activity'] = $now;

    sendJson([
        'auth' => true,
        'expires_in' => max(0, $timeout - ($now - $authTime)),
    ]);
}

function handleLogout(): void
{
    // Очищаем куки сессии
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            ['expires' => time() - 3600, 'path' => $params['path'], 'domain' => $params['domain'], 'secure' => $params['secure'], 'httponly' => true, 'samesite' => $params['samesite'] ?? 'Lax']
        );
    }

    session_unset();
    session_destroy();
    sendJson(['success' => true, 'message' => 'Выход выполнен']);
}