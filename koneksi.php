<?php
$env = parse_ini_file(__DIR__ . '/.env');

define('DB_HOST', $env['DB_HOST']);
define('DB_USER', $env['DB_USER']);
define('DB_PASS', $env['DB_PASS']);
define('DB_NAME', $env['DB_NAME']);

// create koneksi
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// dicek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// diset charset UTF-8
mysqli_set_charset($conn, "utf8");
?>