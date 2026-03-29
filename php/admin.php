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
    <style>
        /* === Стили для таблицы галереи === */
        .gallery-section {
            margin-top: 40px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
        }

        .gallery-section h2 {
            margin-bottom: 20px;
            color: #fff;
            font-size: 1.5rem;
        }

        .gallery-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            overflow: hidden;
        }

        .gallery-table thead {
            background: rgba(0, 0, 0, 0.3);
        }

        .gallery-table th {
            padding: 12px;
            text-align: left;
            color: #fff;
            font-weight: 600;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .gallery-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .gallery-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.1);
            transition: background 0.2s ease;
        }

        .item-preview {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            background: rgba(0, 0, 0, 0.3);
        }

        .delete-btn {
            padding: 8px 16px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .delete-btn:hover {
            background: #c0392b;
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
        }

        .delete-btn:active {
            transform: scale(0.98);
        }

        .empty-gallery {
            text-align: center;
            padding: 40px;
            color: rgba(255, 255, 255, 0.5);
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: rgba(255, 255, 255, 0.7);
        }
    </style>
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

    <!-- === СЕКЦИЯ УПРАВЛЕНИЯ ГАЛЕРЕЕЙ === -->
    <section class="gallery-section">
        <h2>📸 Управление галереей</h2>
        <div id="gallery-container" class="loading">Загрузка галереи...</div>
    </section>

    <!-- Скрипты -->
    <script src="/js/admin.js"></script>
    <script>
        // === Загрузка и отображение галереи ===
        async function loadGallery(type = 'gallery') {
            try {
                const response = await fetch('/php/list.php', {
                    method: 'GET',
                    credentials: 'include'
                });

                if (!response.ok) throw new Error('Ошибка загрузки');

                let items = await response.json();

                // Фильтруем по типу (галерея или магазин)
                items = items.filter(item => item.target === type);

                const container = document.getElementById('gallery-container');

                if (!items || items.length === 0) {
                    const emptyText = type === 'gallery' ? '📭 Галерея пуста' : '📭 Магазин пуст';
                    container.innerHTML = `<div class="empty-gallery">${emptyText}</div>`;
                    return;
                }

                // Создаём таблицу
                let html = `
            <table class="gallery-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Превью</th>
                        <th style="flex: 1;">Название</th>
                        <th style="width: 150px;">Дата создания</th>
        `;

                // Добавляем столбец Цена только для магазина
                if (type === 'shop') {
                    html += `<th style="width: 100px;">Цена</th>`;
                }

                html += `
                        <th style="width: 120px;">Действие</th>
                    </tr>
                </thead>
                <tbody>
        `;

                items.forEach(item => {
                    // Правильный парсинг даты
                    const uploadedAt = item.uploaded_at || '';
                    const dateObj = uploadedAt ? new Date(uploadedAt.replace(' ', 'T')) : null;
                    const date = dateObj && !Number.isNaN(dateObj.getTime())
                        ? dateObj.toLocaleDateString('ru-RU', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit'
                        }) + ' ' + dateObj.toLocaleTimeString('ru-RU', {
                            hour: '2-digit',
                            minute: '2-digit'
                        })
                        : '—';

                    // Путь к картине + защита от старого кеша
                    const imagePath = item.path || (item.filename ? `/${item.target}/${item.filename}` : '');
                    const imageVersion = uploadedAt ? `?v=${encodeURIComponent(uploadedAt)}` : '';

                    html += `
                <tr>
                    <td><img src="${imagePath}${imageVersion}" alt="${item.name}" class="item-preview" onerror="this.src='/img/placeholder.png'"></td>
                    <td>${item.name}</td>
                    <td>${date}</td>
            `;


                    // Добавляем цену только для магазина
                    if (type === 'shop') {
                        html += `<td>${item.price || '—'}₽</td>`;
                    }

                    html += `
                    <td>
                        <button class="delete-btn" onclick="deleteGalleryItem(${item.id}, '${item.target}')">
                            🗑️ Удалить
                        </button>

                    </td>
                </tr>
            `;
                });

                html += `
                </tbody>
            </table>
        `;

                container.innerHTML = html;
            } catch (error) {
                console.error('Ошибка:', error);
                document.getElementById('gallery-container').innerHTML =
                    `<div class="empty-gallery">❌ Ошибка загрузки: ${error.message}</div>`;
            }
        }

        // === Функция удаления ===
        async function deleteGalleryItem(itemId, itemType) {
            if (!confirm(`Вы уверены, что хотите удалить этот товар?`)) {
                return;
            }

            try {
                const response = await fetch('/php/list.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: itemId, target: itemType }),
                    credentials: 'include'
                });


                const data = await response.json();

                if (data.success) {
                    showNotification('✓ Товар успешно удалён!', 'success');
                    // Перезагружаем текущий тип
                    const currentType = document.querySelector('input[name="upload-type"]:checked').value;
                    setTimeout(() => loadGallery(currentType), 1000);
                } else {
                    showNotification(`❌ Ошибка: ${data.error}`, 'error');
                }
            } catch (error) {
                showNotification(`❌ Ошибка соединения: ${error.message}`, 'error');
            }
        }

        // === Уведомление ===
        function showNotification(message, type = 'info') {
            const div = document.createElement('div');
            div.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
        color: white;
        border-radius: 8px;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    `;
            div.textContent = message;
            document.body.appendChild(div);

            setTimeout(() => {
                div.style.opacity = '0';
                div.style.transform = 'translateX(100px)';
                setTimeout(() => div.remove(), 300);
            }, 3000);
        }

        // === Загрузка при старте ===
        document.addEventListener('DOMContentLoaded', () => {
            loadGallery('gallery');

            // === Переключение между галереей и магазином ===
            const uploadTypeRadios = document.querySelectorAll('input[name="upload-type"]');
            uploadTypeRadios.forEach(radio => {
                radio.addEventListener('change', (e) => {
                    loadGallery(e.target.value);
                });
            });
        });

        // === Перезагрузка после успешной загрузки ===
        const originalUploadBtn = document.getElementById('upload-btn');
        if (originalUploadBtn) {
            originalUploadBtn.addEventListener('click', () => {
                setTimeout(() => {
                    const currentType = document.querySelector('input[name="upload-type"]:checked').value;
                    loadGallery(currentType);
                }, 2000);
            });
        }
    </script>
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