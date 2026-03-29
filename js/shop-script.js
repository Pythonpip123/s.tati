let adminPassword = "";

function toggleMenu() {
  const mobileMenu = document.getElementById("mobileMenu");
  const overlay = document.getElementById("mobileMenuOverlay");
  const burger = document.getElementById("burgerMenu");

  if (!mobileMenu || !overlay || !burger) {
    return;
  }

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

function initPageTransitions() {
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
}

function initItemReveal() {
  const cards = document.querySelectorAll(".shop-grid .item-card");

  if (!cards.length || typeof IntersectionObserver === "undefined") {
    cards.forEach((card) => card.classList.add("is-visible"));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        }
      });
    },
    {
      threshold: 0.12,
      rootMargin: "0px 0px -20px 0px",
    },
  );

  cards.forEach((card) => observer.observe(card));
}

let progressBar = null;
let current = 0;
let target = 0;

function updateScrollProgress() {
  const scrollTop = window.scrollY;
  const height = document.documentElement.scrollHeight - window.innerHeight;

  target = height > 0 ? (scrollTop / height) * 100 : 0;
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

function initScrollHandler() {
  let isScrolling = false;

  window.addEventListener("scroll", () => {
    updateScrollProgress();

    if (!isScrolling) {
      requestAnimationFrame(() => {
        if (typeof updateBackToTop === "function") updateBackToTop();
        if (typeof updateHeader === "function") updateHeader();
        if (typeof updateArrowProgress === "function") updateArrowProgress();
        isScrolling = false;
      });

      isScrolling = true;
    }
  });
}

function escapeHtml(text) {
  if (!text) return "";
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

function formatPrice(price) {
  const numericPrice = Number.parseFloat(price);

  if (Number.isNaN(numericPrice)) {
    return "";
  }

  return numericPrice.toLocaleString("ru-RU", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });
}

function normalizeImagePath(path) {
  if (!path) return "";

  if (/^https?:\/\//i.test(path) || path.startsWith("../")) {
    return path;
  }

  if (path.startsWith("/gallery/") || path.startsWith("/shop/")) {
    return `..${path}`;
  }

  return path.startsWith("/") ? `..${path}` : path;
}

function createShopCard(item) {
  const imagePath = normalizeImagePath(item.path || item.image || "");
  const title = item.name || item.title || "Без названия";
  const price = formatPrice(item.price);

  const card = document.createElement("article");
  card.className = "item-card shop-card";

  card.innerHTML = `
    <div class="shop-card-media">
      <img src="${imagePath}" alt="${escapeHtml(title)}" loading="lazy">
      <div class="item-info shop-card-info">
        <div class="shop-card-copy">
          <h3 class="item-title">${escapeHtml(title)}</h3>
          <p class="shop-card-subtitle">Оригинальная работа</p>
        </div>
        <p class="item-price">${price ? `${price} ₽` : "Цена по запросу"}</p>
      </div>
    </div>
  `;

  return card;
}

async function loadShop() {
  const container =
    document.querySelector(".shop-container") ||
    document.querySelector(".shop-grid") ||
    document.getElementById("shopGrid");

  if (!container) {
    console.error("Shop container not found");
    return;
  }

  container.innerHTML = "";

  try {
    const res = await fetch("../php/get-items.php?target=shop");

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}: ${res.statusText}`);
    }

    const items = await res.json();

    if (!Array.isArray(items) || items.length === 0) {
      container.innerHTML = '<p class="shop-state empty">Магазин пока пуст</p>';
      return;
    }

    items.forEach((item) => {
      container.appendChild(createShopCard(item));
    });

    initItemReveal();
  } catch (err) {
    console.error("Failed to load shop:", err);
    container.innerHTML = `<p class="shop-state error">⚠️ Ошибка загрузки: ${err.message}</p>`;
  }
}

window.addEventListener("load", () => {
  document.body.classList.add("loaded");
});

document.addEventListener("DOMContentLoaded", () => {
  progressBar = document.getElementById("scrollProgress");
  initPageTransitions();
  handleNavigation();
  initScrollHandler();
  animateProgress();
  document.body.classList.add("loaded");

  const adminPasswordInput = document.getElementById("adminPassword");
  if (adminPasswordInput) {
    adminPasswordInput.addEventListener("keydown", (event) => {
      if (event.key === "Enter" && typeof checkPassword === "function") {
        checkPassword();
      }
    });
  }

  loadShop();
});
