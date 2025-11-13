<?php
session_start();

// hapus all session
$_SESSION = [];

// Hapus cookie session if there are
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// destroy session
session_destroy();

// halaman login
header('Location: index.php');
exit;