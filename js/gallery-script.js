let adminPassword = "";

function toggleMenu() {
  const mobileMenu = document.getElementById("mobileMenu");
  const overlay = document.getElementById("mobileMenuOverlay");
  const burger = document.getElementById("burgerMenu");

  const isOpen = mobileMenu.classList.contains("active");

  const scrollBarWidth =
    window.innerWidth - document.documentElement.clientWidth;

  if (!isOpen) {
    document.body.style.paddingRight = scrollBarWidth + "px";

    setTimeout(() => {
      document.body.style.overflow = "hidden";
    }, 50);
  } else {
    document.body.style.overflow = "auto";

    setTimeout(() => {
      document.body.style.paddingRight = "0px";
    }, 50);
  }

  mobileMenu.classList.toggle("active");
  overlay.classList.toggle("active");
  burger.classList.toggle("active");
}

// ===== ПЛАВНЫЙ ПЕРЕХОД МЕЖДУ СТРАНИЦАМИ =====
document.querySelectorAll("a[href]").forEach((link) => {
  const url = link.getAttribute("href");

  if (
    url &&
    !url.startsWith("#") &&
    !url.startsWith("http") &&
    !link.hasAttribute("target")
  ) {
    link.addEventListener("click", function (e) {
      e.preventDefault();

      document.body.classList.add("fade-out");

      setTimeout(() => {
        window.location.href = url;
      }, 400);
    });
  }
});

// ===== PINTEREST АНИМАЦИЯ =====
function initMasonryAnimation() {
  const images = document.querySelectorAll(".masonry img");

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
          const el = entry.target;

          setTimeout(() => {
            el.classList.add("show");
          }, index * 100);
        }
      });
    },
    {
      threshold: 0.1,
    },
  );

  images.forEach((img) => {
    observer.observe(img);
  });
}

// ===== КАСТОМНАЯ ПЛАВНАЯ ПОЛОСКА ЗАГРУЗКИ =====
let progressBar = null;
let current = 0;
let target = 0;

function updateScrollProgress() {
  const scrollTop = window.scrollY;
  const height = document.documentElement.scrollHeight - window.innerHeight;

  target = (scrollTop / height) * 100;
}

function animateProgress() {
  current += (target - current) * 0.08;

  if (progressBar) {
    progressBar.style.width = current + "%";
  }

  requestAnimationFrame(animateProgress);
}

function handleNavigation() {
  const links = document.querySelectorAll(".nav-link, .mobile-nav-link");

  links.forEach((link) => {
    link.addEventListener("click", function (e) {
      const href = this.getAttribute("href");

      // только если якорь (локальный)
      if (href === "#hero") {
        e.preventDefault();

        window.scrollTo({
          top: 0,
          behavior: "smooth",
        });
      }
    });
  });
}

// ===== Scroll Handler (optimized) =====
function initScrollHandler() {
  let isScrolling = false;

  window.addEventListener("scroll", () => {
    updateScrollProgress();

    if (!isScrolling) {
      requestAnimationFrame(() => {
        updateBackToTop();
        updateHeader();
        updateArrowProgress();
        isScrolling = false;
      });

      isScrolling = true;
    }
  });
}

// ===== ГАЛЕРЕЯ: ЗАГРУЗКА КАРТИНОК =====
async function loadGallery() {
  try {
    // ✅ Проверь правильный путь к API!
    const res = await fetch("/php/get-items.php?target=gallery");

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}: ${res.statusText}`);
    }

    const items = await res.json();
    console.log("Gallery items loaded:", items);

    // ✅ Правильный селектор (без пробелов!)
    const container = document.querySelector(".gallery-container");

    if (!container) {
      console.error("Container .gallery-container not found!");
      return;
    }

    // Очистка
    container.innerHTML = "";

    if (items.length === 0) {
      container.innerHTML = '<p class="empty">Галерея пуста</p>';
      return;
    }

    // Рендер карточек
    items.forEach((item) => {
      // ✅ Исправлено: => без пробела
      const card = document.createElement("div"); // ✅ Без пробелов
      card.className = "item-card";

      card.innerHTML = `
                <img src="${item.path}" alt="${escapeHtml(item.name)}" loading="lazy">
                <div class="item-info">
                    <h3 class="item-title">${escapeHtml(item.name)}</h3>
                </div>
            `;

      container.appendChild(card);
    });

    // Перезапуск анимации появления
    if (typeof initMasonryAnimation === "function") {
      initMasonryAnimation();
    }
  } catch (err) {
    console.error("Failed to load gallery:", err);
    const container = document.querySelector(".gallery-container");
    if (container) {
      container.innerHTML = `<p class="error">⚠️ Ошибка загрузки: ${err.message}</p>`;
    }
  }
}

function escapeHtml(text) {
  if (!text) return "";
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

window.addEventListener("load", () => {
  document.body.classList.add("loaded");
});

// ===== INIT =====
document.addEventListener("DOMContentLoaded", () => {
  const uploadArea = document.getElementById("uploadArea");
  const fileInput = document.getElementById("fileInput");

  if (!uploadArea || !fileInput) {
    return;
  }

  uploadArea.addEventListener("dragover", (e) => {
    e.preventDefault();
  });

  uploadArea.addEventListener("drop", (e) => {
    e.preventDefault();

    handleFiles(e.dataTransfer.files, adminPassword);
  });

  uploadArea.addEventListener("click", () => {
    fileInput.click();
  });

  fileInput.addEventListener("change", () => {
    handleFiles(fileInput.files, adminPassword);
  });
  document.body.classList.add("loaded");
  initMasonryAnimation();
  loadGallery();
});
