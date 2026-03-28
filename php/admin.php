<?php
declare(strict_types=1);

/**
 * admin.php — Защищённая страница админ-панели
 * Доступ только при активной сессии (5 минут)
 */

// === 1. ПРОВЕРКА АВТОРИЗАЦИИ (ДО ЛЮБОГО ВЫВОДА!) ===
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Старт сессии (если ещё не стартовала)
if (session_status() === PHP_SESSION_NONE) {
    // Настройки совместимые с PHP 8.4
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '0'); // true если только HTTPS
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.lazy_write', '1');
    session_start();
}

// Проверка: авторизован ли пользователь
$isAdmin = $_SESSION['admin'] ?? false;
$timeout = 300; // 5 минут
$now = time();
$authTime = $_SESSION['auth_time'] ?? 0;

// Если не авторизован ИЛИ сессия истекла
if (!$isAdmin || ($now - $authTime) > $timeout) {
    // Очищаем сессию, если истекла
    if (!$isAdmin || ($now - $authTime) > $timeout) {
        session_unset();
        session_destroy();
    }

    // Отправляем 403 и показываем страницу ошибки
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    // Можно редирект, но лучше показать красивую 403:
    echo get403Page();
    exit; // Критически важно: остановить выполнение!
}

// Обновляем время активности сессии
$_SESSION['last_activity'] = $now;

// === 2. Если авторизован — показываем админку ===
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | s.tati</title>
    <link rel="stylesheet" href="/css/admin.css">
    <link rel="icon" href="../favicon.png" type="png/image">
</head>

<body>
    <!-- Хедер -->
    <header class="admin-header">
        <h1>⚙️ Админ-панель</h1>
        <button id="logout-btn" class="exit-btn" title="Выйти">Выйти</button>
    </header>

    <!-- Секция загрузки -->
    <main class="upload-section">
        <!-- Переключатель: Галерея / Магазин -->
        <div class="slider-container">
            <label>
                <input type="radio" name="upload-type" value="gallery" checked>
                <span>🖼️ Галерея</span>
            </label>
            <label>
                <input type="radio" name="upload-type" value="shop">
                <span>🛒 Магазин</span>
            </label>
        </div>

        <!-- Drag & Drop зона -->
        <div id="drop-zone" class="drop-zone" tabindex="0">
            <p>📁 Перетащите изображение сюда</p>
            <small>или нажмите для выбора файла</small>
            <small style="display:block;margin-top:8px;opacity:0.7">JPG, PNG, WebP до 10 МБ</small>
            <input type="file" id="file-input" accept="image/jpeg,image/jpg,image/png,image/webp" hidden>
        </div>

        <!-- Поля ввода -->
        <div id="form-fields">
            <input type="text" id="item-name" placeholder="Название изображения / товара" maxlength="100" required>
            <div id="price-field" style="display:none;">
                <input type="number" id="item-price" placeholder="Цена (₽)" min="0" step="0.01">
            </div>
        </div>

        <!-- Кнопка -->
        <button id="upload-btn" class="green-btn">🚀 Загрузить</button>

        <!-- Статус -->
        <div id="upload-status"></div>
    </main>

    <!-- Скрипты -->
    <script src="/js/admin.js"></script>
</body>

</html>

<?php
// === Вспомогательная функция: страница 403 ===
function get403Page(): string
{
    return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Доступ запрещён | s.tati</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-card {
            background: rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 40px;
            max-width: 480px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
        }
        .error-code {
            font-size: 4rem;
            font-weight: 700;
            color: #e74c3c;
            margin-bottom: 16px;
            text-shadow: 0 4px 20px rgba(231,76,60,0.4);
        }
        .error-title {
            font-size: 1.5rem;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .error-desc {
            color: rgba(255,255,255,0.7);
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            padding: 12px 28px;
            background: #27ae60;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #219a52;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39,174,96,0.4);
        }
        .btn-outline {
            background: transparent;
            border: 2px solid rgba(255,255,255,0.3);
            margin-right: 12px;
        }
        .btn-outline:hover {
            background: rgba(255,255,255,0.1);
        }
        .actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">403</div>
        <h1 class="error-title">Доступ запрещён</h1>
        <p class="error-desc">
            Ваша сессия истекла или вы не авторизованы.<br>
            Пожалуйста, войдите в админ-панель снова.
        </p>
        <div class="actions">
            <a href="/index.html" class="btn btn-outline">На главную</a>
            <button class="btn" onclick="openAdminLogin()">Войти в админку</button>
        </div>
    </div>
    
    <script>
        function openAdminLogin() {
            // Проверяем, есть ли функция из admin.js
            if (typeof showPasswordModal === 'function') {
                showPasswordModal();
            } else {
                // Если нет — редирект на главную с триггером
                window.location.href = '/index.html?admin=1';
            }
        }
        
        // Авто-редирект через 10 секунд (опционально)
        setTimeout(() => {
            if (!document.querySelector('.admin-modal')) {
                // Если модальное окно не открылось — можно редиректить
                // window.location.href = '/index.html';
            }
        }, 10000);
    </script>
</body>
</html>
HTML;
}
?>