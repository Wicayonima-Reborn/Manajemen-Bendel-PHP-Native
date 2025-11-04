<?php
// Path .env
$envPath = __DIR__ . '/.env';

// Cek apakah file .env ada
if (!file_exists($envPath)) {
    die("Config error: file .env tidak ditemukan");
}
// Load .env
$env = parse_ini_file($envPath);

if (!$env) {
    die("Config error: gagal membaca .env");
}

// Ambil env
$db_host = $env['DB_HOST'] ?? 'localhost';
$db_user = $env['DB_USER'] ?? '';
$db_pass = $env['DB_PASS'] ?? '';
$db_name = $env['DB_NAME'] ?? '';

// Koneksi
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Charset
mysqli_set_charset($conn, "utf8mb4");