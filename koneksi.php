<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '123');
define('DB_NAME', 'bendel_db');

// create koneksi
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// dicek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// diset charset UTF-8
mysqli_set_charset($conn, "utf8");
?>