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

function initMasonryAnimation() {
  const cards = document.querySelectorAll(".gallery-container .gallery-card");

  if (!cards.length) {
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("show");
          observer.unobserve(entry.target);
        }
      });
    },
    {
      threshold: 0.08,
      rootMargin: "0px 0px 60px 0px",
    },
  );

  cards.forEach((card, index) => {
    card.style.transitionDelay = `${Math.min(index * 45, 240)}ms`;
    observer.observe(card);
  });
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

function getAspectRatioClass(item) {
  const width = Number(item.width) || Number(item.imageWidth) || 0;
  const height = Number(item.height) || Number(item.imageHeight) || 0;

  if (width && height) {
    const ratio = width / height;

    if (ratio >= 1.45) return "is-landscape";
    if (ratio <= 0.8) return "is-portrait";
    return "is-square";
  }

  const name = `${item.name || ""} ${item.path || ""}`.toLowerCase();

  if (name.includes("panorama") || name.includes("wide")) {
    return "is-landscape";
  }

  return "is-auto";
}

async function loadGallery() {
  try {
    const res = await fetch("../php/get-items.php?target=gallery");

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}: ${res.statusText}`);
    }

    const items = await res.json();
    const container = document.querySelector(".gallery-container");

    if (!container) {
      console.error("Container .gallery-container not found!");
      return;
    }

    container.innerHTML = "";

    if (!Array.isArray(items) || items.length === 0) {
      container.innerHTML = '<p class="empty">Галерея пуста</p>';
      return;
    }

    items.forEach((item) => {
      const card = document.createElement("article");
      card.className = `item-card gallery-card ${getAspectRatioClass(item)}`;
      card.innerHTML = `
        <div class="gallery-media">
          <img src="${normalizeImagePath(item.path)}" alt="${escapeHtml(item.name)}" loading="lazy">
          <div class="item-info">
            <h3 class="item-title">${escapeHtml(item.name)}</h3>
          </div>
        </div>
      `;

      container.appendChild(card);
    });

    initMasonryAnimation();
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

document.addEventListener("DOMContentLoaded", () => {
  progressBar = document.getElementById("scrollProgress");
  handleNavigation();
  initScrollHandler();
  animateProgress();
  document.body.classList.add("loaded");

  const uploadArea = document.getElementById("uploadArea");
  const fileInput = document.getElementById("fileInput");

  if (uploadArea && fileInput) {
    uploadArea.addEventListener("dragover", (e) => {
      e.preventDefault();
    });

    uploadArea.addEventListener("drop", (e) => {
      e.preventDefault();

      if (typeof handleFiles === "function") {
        handleFiles(e.dataTransfer.files, adminPassword);
      }
    });

    uploadArea.addEventListener("click", () => {
      fileInput.click();
    });

    fileInput.addEventListener("change", () => {
      if (typeof handleFiles === "function") {
        handleFiles(fileInput.files, adminPassword);
      }
    });
  }

  loadGallery();
});