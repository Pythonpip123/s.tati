<?php
session_start();

if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
  header("Location: ../html/index.html");
  exit;
}
?>

<!doctype html>
<html lang="ru">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Админ панель</title>

  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/gallery.css" />

  <style>
    body {
      background: #111;
      color: white;
      font-family: Montserrat, sans-serif;
      padding: 20px;
    }

    .admin-container {
      max-width: 600px;
      margin: auto;
    }

    h1 {
      text-align: center;
      margin-bottom: 30px;
    }

    input,
    button {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border: none;
      border-radius: 8px;
    }

    input {
      background: #222;
      color: white;
    }

    button {
      background: #fff;
      color: #000;
      cursor: pointer;
      font-weight: 600;
    }

    button:hover {
      opacity: 0.8;
    }

    .hidden {
      display: none;
    }

    .preview img {
      width: 100%;
      margin-top: 10px;
      border-radius: 10px;
    }
  </style>
</head>

<body>
  <div class="admin-container">
    <h1>Админ панель</h1>

    <!-- LOGIN -->
    <div id="loginBlock">
      <input type="password" id="password" placeholder="Введите пароль" />
      <button onclick="login()">Войти</button>
      <button onclick="logout()">Выйти</button>
    </div>

    <!-- PANEL -->
    <div id="adminPanel" class="hidden">
      <h2>Загрузка в галерею</h2>

      <input type="file" id="galleryFile" />
      <input type="text" id="galleryTitle" placeholder="Название" />

      <button onclick="uploadGallery()">Загрузить в галерею</button>

      <hr />

      <h2>Загрузка в магазин</h2>

      <input type="file" id="shopFile" />
      <input type="text" id="shopTitle" placeholder="Название" />
      <input type="text" id="shopPrice" placeholder="Цена" />

      <button onclick="uploadShop()">Загрузить в магазин</button>

      <div class="preview" id="preview"></div>
    </div>
  </div>

  <script src="../js/admin.js"></script>
</body>

</html>