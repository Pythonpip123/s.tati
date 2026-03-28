<?php
require "check_auth.php";

$file = "../data/shop.json";
echo file_exists($file) ? file_get_contents($file) : "[]";