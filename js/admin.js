let adminPassword = "";

// ===== LOGIN =====
function login() {
  const input = document.getElementById("password");

  fetch("../php/auth.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "password=" + encodeURIComponent(input.value),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        showAdmin();
      } else {
        alert(data.error);
      }
    });
}

function logout() {
  fetch("../php/logout.php").then(() => {
    location.reload();
  });
}

// ===== UPLOAD В ГАЛЕРЕЮ =====
function uploadGallery() {
  const fileInput = document.getElementById("galleryFile");
  const titleInput = document.getElementById("galleryTitle");

  if (!fileInput.files.length) {
    alert("Выбери файл");
    return;
  }

  const formData = new FormData();
  formData.append("file", fileInput.files[0]);
  formData.append("password", adminPassword);
  formData.append("title", titleInput.value);

  fetch("../php/upload.php", {
    method: "POST",
    body: formData,
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.error) {
        alert(data.error);
        return;
      }

      showPreview(data.url, data.title);
      alert("Загружено в галерею");
    })
    .catch(() => alert("Ошибка загрузки"));
}

// ===== UPLOAD В МАГАЗИН =====
function uploadShop() {
  const fileInput = document.getElementById("shopFile");
  const titleInput = document.getElementById("shopTitle");
  const priceInput = document.getElementById("shopPrice");

  if (!fileInput.files.length) {
    alert("Выбери файл");
    return;
  }

  const formData = new FormData();
  formData.append("file", fileInput.files[0]);
  formData.append("title", titleInput.value);
  formData.append("price", priceInput.value);
  formData.append("password", adminPassword);

  fetch("../php/upload_shop.php", {
    method: "POST",
    body: formData,
  })
    .then((res) => res.json())
    .then((data) => {
      showPreview(data.url, data.title, data.price);
      alert("Загружено в магазин");
    })
    .catch(() => alert("Ошибка загрузки"));
}

// ===== PREVIEW =====
function showPreview(url, title = "", price = "") {
  const preview = document.getElementById("preview");

  const block = document.createElement("div");

  block.innerHTML = `
    <img src="${url}">
    <p>${title}</p>
    ${price ? `<p>${price} ₽</p>` : ""}
    <hr/>
  `;

  preview.prepend(block);
}

document.addEventListener("DOMContentLoaded", () => {
  fetch("../php/auth.php")
    .then((res) => res.json())
    .then((data) => {
      if (data.auth) {
        showAdmin();
      }
    });
});

function showAdmin() {
  document.getElementById("loginBlock").classList.add("hidden");
  document.getElementById("adminPanel").classList.remove("hidden");
}
