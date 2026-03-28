/**
 * Админ-панель: обработка клавиш, авторизация, загрузка файлов
 */

// === Глобальные константы ===
const ADMIN_SHORTCUT = { ctrl: true, shift: true, key: "F" };
const SESSION_CHECK_INTERVAL = 60000; // 1 минута
const DROP_ZONE = document.getElementById("drop-zone");
const FILE_INPUT = document.getElementById("file-input");
const UPLOAD_TYPE = document.getElementsByName("upload-type");
const PRICE_FIELD = document.getElementById("price-field");
const UPLOAD_BTN = document.getElementById("upload-btn");
const STATUS = document.getElementById("upload-status");
const LOGOUT_BTN = document.getElementById("logout-btn");

// === Инициализация ===
document.addEventListener("DOMContentLoaded", () => {
  setupKeyboardShortcut();
  setupDropZone();
  setupTypeSwitcher();
  setupUploadButton();
  setupLogout();
  startSessionCheck();
});

// === Сочетание клавиш Ctrl+Shift+F ===
function setupKeyboardShortcut() {
  document.addEventListener("keydown", (e) => {
    if (
      e.ctrlKey === ADMIN_SHORTCUT.ctrl &&
      e.shiftKey === ADMIN_SHORTCUT.shift &&
      e.key.toLowerCase() === ADMIN_SHORTCUT.key.toLowerCase()
    ) {
      e.preventDefault();
      handleAdminAccess();
    }
  });
}

async function handleAdminAccess() {
  try {
    const res = await fetch("/php/auth.php", {
      method: "GET",
      credentials: "include",
    });
    const data = await res.json();

    if (data.auth) {
      window.location.href = "/admin.html";
    } else {
      showPasswordModal();
    }
  } catch (err) {
    console.error("Ошибка проверки авторизации:", err);
    showPasswordModal();
  }
}

// === Модальное окно пароля ===
function showPasswordModal() {
  if (document.querySelector(".admin-modal")) return;

  const modal = document.createElement("div");
  modal.className = "admin-modal";
  modal.innerHTML = `
        <div class="modal-content">
            <h3>🔐 Вход в админ-панель</h3>
            <input type="password" id="admin-pass" placeholder="Введите пароль" autocomplete="current-password">
            <div class="modal-buttons">
                <button class="btn-cancel">Отмена</button>
                <button class="btn-confirm green">Войти</button>
            </div>
        </div>
    `;

  document.body.appendChild(modal);

  const input = modal.querySelector("#admin-pass");
  const confirmBtn = modal.querySelector(".btn-confirm");
  const cancelBtn = modal.querySelector(".btn-cancel");

  // Фокус на поле ввода
  setTimeout(() => input.focus(), 100);

  // Обработчики
  confirmBtn.onclick = async () => {
    const password = input.value.trim();
    if (!password) {
      showError("Введите пароль", modal);
      return;
    }

    confirmBtn.disabled = true;
    confirmBtn.textContent = "Проверка...";

    try {
      const res = await fetch("/php/auth.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ password }),
        credentials: "include",
      });
      const data = await res.json();

      if (data.success) {
        modal.remove();
        window.location.href = "/admin.html";
      } else {
        showError(data.error || "Ошибка авторизации", modal);
        input.value = "";
        input.focus();
      }
    } catch (err) {
      showError("Ошибка соединения", modal);
    } finally {
      confirmBtn.disabled = false;
      confirmBtn.textContent = "Войти";
    }
  };

  cancelBtn.onclick = () => modal.remove();

  // Закрытие по Escape
  modal.onkeydown = (e) => {
    if (e.key === "Escape") modal.remove();
    if (e.key === "Enter") confirmBtn.click();
  };

  // Закрытие по клику вне окна
  modal.onclick = (e) => {
    if (e.target === modal) modal.remove();
  };
}

function showError(message, container = document.body) {
  const errorDiv = document.createElement("div");
  errorDiv.className = "error-toast";
  errorDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #e74c3c;
        color: white;
        padding: 12px 20px;
        border-radius: 10px;
        z-index: 10000;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        animation: slideIn 0.3s ease;
    `;
  errorDiv.textContent = message;
  container.appendChild(errorDiv);

  setTimeout(() => {
    errorDiv.style.opacity = "0";
    errorDiv.style.transform = "translateX(100px)";
    setTimeout(() => errorDiv.remove(), 300);
  }, 3000);
}

// === Drag & Drop зона ===
function setupDropZone() {
  if (!DROP_ZONE || !FILE_INPUT) return;

  // Клик по зоне = выбор файла
  DROP_ZONE.onclick = () => FILE_INPUT.click();

  // Drag events
  ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
    DROP_ZONE.addEventListener(eventName, (e) => {
      e.preventDefault();
      e.stopPropagation();
    });
  });

  ["dragenter", "dragover"].forEach((eventName) => {
    DROP_ZONE.addEventListener(eventName, () => {
      DROP_ZONE.classList.add("dragover");
    });
  });

  ["dragleave", "drop"].forEach((eventName) => {
    DROP_ZONE.addEventListener(eventName, () => {
      DROP_ZONE.classList.remove("dragover");
    });
  });

  // Обработка drop
  DROP_ZONE.addEventListener("drop", (e) => {
    const files = e.dataTransfer.files;
    if (files.length) handleFileSelect(files[0]);
  });

  // Обработка выбора через диалог
  FILE_INPUT.addEventListener("change", (e) => {
    if (e.target.files.length) handleFileSelect(e.target.files[0]);
  });
}

function handleFileSelect(file) {
  // Валидация типа
  const validTypes = ["image/jpeg", "image/jpg", "image/png", "image/webp"];
  if (!validTypes.includes(file.type)) {
    showError("Разрешены только: JPG, PNG, WebP");
    return;
  }

  // Валидация размера (10 МБ)
  if (file.size > 10 * 1024 * 1024) {
    showError("Файл не должен превышать 10 МБ");
    return;
  }

  // Показываем превью (опционально)
  const reader = new FileReader();
  reader.onload = (e) => {
    DROP_ZONE.innerHTML = `<img src="${e.target.result}" style="max-height:200px;border-radius:12px;">`;
  };
  reader.readAsDataURL(file);

  // Сохраняем файл для отправки
  DROP_ZONE.dataset.file = JSON.stringify({
    name: file.name,
    type: file.type,
    size: file.size,
  });
}

// === Переключатель типа загрузки ===
function setupTypeSwitcher() {
  UPLOAD_TYPE.forEach((radio) => {
    radio.addEventListener("change", () => {
      const isShop =
        document.querySelector('input[name="upload-type"]:checked').value ===
        "shop";
      PRICE_FIELD.style.display = isShop ? "block" : "none";
    });
  });
}

// === Кнопка загрузки ===
function setupUploadButton() {
  if (!UPLOAD_BTN) return;

  UPLOAD_BTN.addEventListener("click", async () => {
    const target = document.querySelector(
      'input[name="upload-type"]:checked',
    )?.value;
    const name = document.getElementById("item-name")?.value.trim();
    const price = document.getElementById("item-price")?.value;
    const fileData = DROP_ZONE.dataset.file;

    // Валидация
    if (!target) {
      showError("Выберите тип загрузки");
      return;
    }
    if (!name) {
      showError("Введите название");
      return;
    }
    if (
      target === "shop" &&
      (!price || isNaN(price) || parseFloat(price) < 0)
    ) {
      showError("Введите корректную цену");
      return;
    }
    if (!fileData) {
      showError("Выберите изображение");
      return;
    }

    // Подготовка FormData
    const formData = new FormData();
    formData.append("target", target);
    formData.append("name", name);
    if (target === "shop") formData.append("price", price);

    // Добавляем файл из input (если есть)
    if (FILE_INPUT.files.length) {
      formData.append("image", FILE_INPUT.files[0]);
    }

    // UI: показываем загрузку
    UPLOAD_BTN.disabled = true;
    UPLOAD_BTN.textContent = "Загрузка...";
    STATUS.className = "";
    STATUS.style.display = "none";

    try {
      const res = await fetch("/php/upload.php", {
        method: "POST",
        body: formData,
        credentials: "include",
      });

      const data = await res.json();

      if (res.ok && data.success) {
        STATUS.className = "success";
        STATUS.textContent = `✓ ${data.message}`;
        STATUS.style.display = "block";

        // Сброс формы
        if (FILE_INPUT) FILE_INPUT.value = "";
        if (document.getElementById("item-name"))
          document.getElementById("item-name").value = "";
        if (document.getElementById("item-price"))
          document.getElementById("item-price").value = "";
        DROP_ZONE.innerHTML =
          "<p>Перетащите изображение сюда или нажмите для выбора</p><small>JPG, PNG, WebP до 10 МБ</small>";
        delete DROP_ZONE.dataset.file;
      } else {
        throw new Error(data.error || "Ошибка загрузки");
      }
    } catch (err) {
      STATUS.className = "error";
      STATUS.textContent = `✗ ${err.message}`;
      STATUS.style.display = "block";
    } finally {
      UPLOAD_BTN.disabled = false;
      UPLOAD_BTN.textContent = "Загрузить";
    }
  });
}

// === Кнопка выхода ===
function setupLogout() {
  if (!LOGOUT_BTN) return;

  LOGOUT_BTN.addEventListener("click", async () => {
    if (!confirm("Вы точно хотите выйти из админ-панели?")) return;

    try {
      await fetch("/php/auth.php", {
        method: "DELETE",
        credentials: "include",
      });
      window.location.href = "/index.html";
    } catch (err) {
      console.error("Ошибка выхода:", err);
      window.location.href = "/index.html";
    }
  });
}

// === Проверка сессии ===
function startSessionCheck() {
  setInterval(async () => {
    if (!window.location.pathname.includes("admin.html")) return;

    try {
      const res = await fetch("/php/auth.php", {
        method: "GET",
        credentials: "include",
      });
      const data = await res.json();

      if (!data.auth) {
        alert("⏰ Сессия истекла. Пожалуйста, войдите снова.");
        window.location.href = "/index.html";
      }
    } catch (err) {
      console.warn("Не удалось проверить сессию:", err);
    }
  }, SESSION_CHECK_INTERVAL);
}

// === CSS для тостов (если нет в admin.css) ===
const style = document.createElement("style");
style.textContent = `
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(100px); }
        to { opacity: 1; transform: translateX(0); }
    }
`;
document.head.appendChild(style);
