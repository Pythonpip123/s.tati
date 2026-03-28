// ===== Заглушка =====
function showAlert(element) {
  alert("Кнопка: " + element + "\n(Это заглушка)");
}

// ===== Mobile Menu =====
function toggleMenu() {
  const mobileMenu = document.getElementById("mobileMenu");
  const mobileOverlay = document.getElementById("mobileMenuOverlay");
  const burgerMenu = document.getElementById("burgerMenu");

  const isOpen = mobileMenu.classList.contains("active");

  const scrollBarWidth =
    window.innerWidth - document.documentElement.clientWidth;

  if (!isOpen) {
    document.body.style.paddingRight = scrollBarWidth + "px";

    // небольшая задержка — чтобы padding начал анимироваться
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
  mobileOverlay.classList.toggle("active");
  burgerMenu.classList.toggle("active");
}

// ===== Scroll To Top =====
function scrollToTop() {
  window.scrollTo({
    top: 0,
    behavior: "smooth",
  });
}

function updateBackToTop() {
  const backToTop = document.getElementById("backToTop");
  if (!backToTop) return;

  if (window.scrollY > 500) {
    backToTop.classList.add("visible");
  } else {
    backToTop.classList.remove("visible");
  }
}

// ===== Header =====
function updateHeader() {
  const header = document.querySelector(".header");
  if (!header) return;

  if (window.scrollY > 50) {
    header.classList.add("scrolled");
  } else {
    header.classList.remove("scrolled");
  }
}

// ===== Arrow Progress =====
function updateArrowProgress() {
  const steps = document.querySelectorAll(".step");
  const arrows = document.querySelectorAll(".arrow-fill");

  steps.forEach((step, index) => {
    const rect = step.getBoundingClientRect();
    const windowHeight = window.innerHeight;

    let progress = 0;

    if (rect.top < windowHeight) {
      const total = windowHeight + rect.height;
      const passed = windowHeight - rect.top;
      progress = passed / total;
    }

    progress = Math.max(0, Math.min(1, progress));

    if (arrows[index]) {
      arrows[index].style.height = progress * 100 + "%";
    }
  });
}

// ===== Scroll Progress Bar =====
let progressBar = null;
let currentProgress = 0;
let targetProgress = 0;

function updateTargetProgress() {
  const scrollTop = window.scrollY;
  const docHeight = document.documentElement.scrollHeight - window.innerHeight;

  if (docHeight <= 0) {
    targetProgress = 0;
    return;
  }

  targetProgress = (scrollTop / docHeight) * 100;
}

function animateProgress() {
  if (!progressBar) return;

  currentProgress += (targetProgress - currentProgress) * 0.08;
  progressBar.style.width = currentProgress + "%";

  requestAnimationFrame(animateProgress);
}

// ===== Smooth Scroll =====
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute("href"));
      if (target) {
        window.scrollTo({
          top: target.offsetTop,
          behavior: "smooth",
        });
      }
    });
  });
}

// ===== Step Animation Delay =====
function initStepDelay() {
  document.querySelectorAll(".step").forEach((step, index) => {
    step.style.transitionDelay = index * 0.15 + "s";
  });
}

// ===== Mobile menu close on click =====
function initMobileLinks() {
  document.querySelectorAll(".mobile-nav-link").forEach((link) => {
    link.addEventListener("click", () => {
      toggleMenu();
    });
  });
}

// ===== Scroll Handler (optimized) =====
function initScrollHandler() {
  let isScrolling = false;

  window.addEventListener("scroll", () => {
    updateTargetProgress();

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

// ===== Intersection Observer =====
function initObserver() {
  if (!("IntersectionObserver" in window)) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("visible");
        }
      });
    },
    {
      threshold: 0.1,
      rootMargin: "0px 0px -100px 0px",
    },
  );

  document.querySelectorAll(".scroll-reveal").forEach((el) => {
    observer.observe(el);
  });
}

// ===== Arrow Animation =====
function animateArrows() {
  const arrows = [
    document.getElementById("arrow1"),
    document.getElementById("arrow2"),
    document.getElementById("arrow3"),
    document.getElementById("arrow4"),
  ];

  arrows.forEach((arrow, index) => {
    setTimeout(() => {
      if (arrow) {
        arrow.style.height = "100%";
      }
    }, index * 300);
  });
}

function openModal() {
  document.getElementById("orderModal").classList.add("active");
}

function closeModal() {
  document.getElementById("orderModal").classList.remove("active");
}

function generateOrderId() {
  let id = localStorage.getItem("orderId") || 0;
  id = parseInt(id) + 1;

  if (id > 1000000) id = 1;

  localStorage.setItem("orderId", id);
  return id;
}

(function () {
  emailjs.init("7f5I7mBy3VSqqloVa");
})();

document.getElementById("orderForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const orderId = generateOrderId();

  const data = {
    to_email: "gamerbarsikd@gmail.com",
    order_id: orderId,
    name: document.getElementById("name").value,
    phone: document.getElementById("phone").value,
    email: document.getElementById("email").value,
    message: document.getElementById("message").value,
  };

  emailjs
    .send("service_ykvn975", "template_46hu43i", data)
    .then(() => {
      alert("Заказ #" + orderId + " отправлен!");
      closeModal();
    })
    .catch((error) => {
      console.log("ERROR FULL:", error);
    });
});

document.getElementById("orderModal").addEventListener("click", (e) => {
  if (e.target.id === "orderModal") {
    closeModal();
  }
});

// Плавный переход между страницами
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

document.addEventListener("keydown", (e) => {
  const isIndex =
    window.location.pathname === "/" ||
    window.location.pathname.includes("index.html");

  if (!isIndex) return;

  if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === "f") {
    e.preventDefault();
    document.getElementById("adminModal").classList.add("active");
  }
});

const loginBtn = document.getElementById("adminLoginBtn");
const input = document.getElementById("adminPasswordInput");

if (loginBtn) {
  loginBtn.addEventListener("click", () => {
    fetch("/php/auth.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: "password=" + encodeURIComponent(input.value),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          window.location.href = "/html/admin.html";
        } else {
          alert("Неверный пароль");
        }
      });
  });
}

function openAdminModal() {
  adminModal.classList.add("active");
  document.body.style.overflow = "hidden";
  adminInput.value = "";
  setTimeout(() => adminInput.focus(), 100);
}

function closeAdminModal() {
  adminModal.classList.remove("active");
  document.body.style.overflow = "auto";
}

// ===== INIT =====
document.addEventListener("DOMContentLoaded", () => {
  progressBar = document.getElementById("scrollProgress");

  updateBackToTop();
  updateHeader();
  updateArrowProgress();

  initSmoothScroll();
  initStepDelay();
  initMobileLinks();
  initScrollHandler();
  initObserver();

  animateProgress(); // теперь запускается ПРАВИЛЬНО

  document.body.classList.add("loaded");
});

// Arrow animation after full load
window.addEventListener("load", animateArrows);
