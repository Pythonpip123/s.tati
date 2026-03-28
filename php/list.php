<?php
require "check_auth.php";

$file = "../data/gallery.json";
echo file_exists($file) ? file_get_contents($file) : "[]";