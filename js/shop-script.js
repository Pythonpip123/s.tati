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

// ===== ОТКРЫТИЕ ПАРОЛЯ =====
document.addEventListener("keydown", (e) => {
  if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === "f") {
    document.getElementById("passwordModal").classList.add("active");
  }
});

// ===== ПРОВЕРКА ПАРОЛЯ =====
function checkPassword() {
  const input = document.getElementById("adminPassword");
  const title = document.getElementById("adminTitle");

  if (input.value === "12345") {
    adminPassword = input.value; // ✅ сохраняем пароль

    document.getElementById("passwordModal").classList.remove("active");
    document.getElementById("uploadModal").classList.add("active");

    input.value = "";
    title.innerText = "Введите пароль";
    title.classList.remove("admin-error");
  } else {
    title.innerText = "Введён неверный пароль";
    title.classList.add("admin-error");
  }
}

// ===== ЗАКРЫТЬ =====
function closeUpload() {
  document.getElementById("uploadModal").classList.remove("active");
}

// ===== DRAG & DROP =====
const uploadArea = document.getElementById("uploadArea");
const fileInput = document.getElementById("fileInput");

// перетаскивание
uploadArea.addEventListener("dragover", (e) => {
  e.preventDefault();
  uploadArea.style.background = "rgba(0,0,0,0.05)";
});

uploadArea.addEventListener("dragleave", () => {
  uploadArea.style.background = "transparent";
});

uploadArea.addEventListener("drop", (e) => {
  e.preventDefault();
  uploadArea.style.background = "transparent";

  handleFiles(e.dataTransfer.files, adminPassword); // ✅ исправлено
});

// клик по области
uploadArea.addEventListener("click", () => {
  fileInput.click();
});

// выбор файлов
fileInput.addEventListener("change", () => {
  handleFiles(fileInput.files, adminPassword);
});

// ===== ЗАГРУЗКА =====
function handleFiles(files, password) {
  const masonry = document.querySelector(".masonry");
  const titleInput = document.getElementById("imageTitle");

  for (let file of files) {
    const formData = new FormData();
    formData.append("file", file);
    formData.append("password", password);
    formData.append("title", titleInput.value); // 🔥

    fetch("../php/upload.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.url) {
          const wrapper = document.createElement("div");
          wrapper.classList.add("gallery-item");

          wrapper.innerHTML = `
            <img src="${data.url}">
            <p>${data.title}</p>
          `;

          masonry.appendChild(wrapper);
        }
      });
  }
}

function loadGallery() {
  const masonry = document.querySelector(".masonry");

  fetch("../php/list.php")
    .then((res) => res.json())
    .then((images) => {
      images.forEach((url, index) => {
        const img = document.createElement("img");
        img.src = url;

        masonry.appendChild(img);

        setTimeout(() => {
          img.classList.add("show");
        }, index * 100);
      });
    })
    .catch((err) => console.error("Ошибка загрузки:", err));
}

function loadShop() {
  fetch("../php/list-shop.php")
    .then((res) => res.json())
    .then((data) => {
      const grid = document.getElementById("shopGrid");
      grid.innerHTML = "";

      data.forEach((item) => {
        addShopItem(item);
      });
    });
}

function addShopItem(data) {
  const grid = document.getElementById("shopGrid");

  const div = document.createElement("div");
  div.classList.add("shop-item");

  div.innerHTML = `
    <img src="${data.url}">
    <div class="shop-info">
      <h3>${data.title}</h3>
      <p>${data.price} €</p>
    </div>
  `;

  grid.appendChild(div);
}

document.getElementById("fileInput").addEventListener("change", function () {
  const file = this.files[0];

  const title = document.getElementById("shopTitle").value;
  const price = document.getElementById("shopPrice").value;

  const formData = new FormData();
  formData.append("file", file);
  formData.append("title", title);
  formData.append("price", price);

  fetch("../php/upload-shop.php", {
    method: "POST",
    body: formData,
  })
    .then((res) => res.json())
    .then((data) => {
      addShopItem(data);
    });
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
  loadShop();
});
