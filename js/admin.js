function checkAuth() {
  fetch("/php/auth.php")
    .then((res) => res.json())
    .then((data) => {
      if (!data.auth) {
        document.body.innerHTML = "Не авторизован";
      }
    });
}

checkAuth();

function logout() {
  window.location.href = "/php/logout.php";
}

function upload() {
  const file = document.getElementById("file").files[0];
  const title = document.getElementById("title").value;
  const price = document.getElementById("price").value;
  const mode = document.getElementById("mode").value;

  const formData = new FormData();
  formData.append("file", file);
  formData.append("title", title);
  formData.append("price", price);

  const url = mode === "gallery" ? "/php/upload.php" : "/php/upload_shop.php";

  fetch(url, {
    method: "POST",
    body: formData,
  }).then(() => load());
}

function load() {
  fetch("/php/list.php")
    .then((res) => res.json())
    .then((data) => {
      const list = document.getElementById("list");
      list.innerHTML = "";

      data.forEach((item) => {
        const div = document.createElement("div");
        div.innerHTML = `
          <img src="${item.image}" width="100">
          <p>${item.title}</p>
          <button onclick="remove('${item.image}')">Удалить</button>
        `;
        list.appendChild(div);
      });
    });
}

function remove(image) {
  fetch("/php/delete.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "image=" + image,
  }).then(() => load());
}

load();
