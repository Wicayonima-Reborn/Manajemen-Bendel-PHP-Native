<?php
/**
 * Security Helper Functions
 * functions ini untuk keamanan semua file PHP
 */

/**
 * Generate CSRF Token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize output untuk prevent XSS
 */
function clean_output($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Cek apakah user sudah login
 */
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Cek role user
 */
function check_role($required_role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Sanitize integer input
 */
function sanitize_int($value) {
    return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Sanitize string input
 */
function sanitize_string($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Rate limiting for prevent brute force)
 */
function check_rate_limit($key, $max_attempts = 5, $time_window = 300) {
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $now = time();
    
    // membersihkan old entries
    foreach ($_SESSION['rate_limit'] as $k => $data) {
        if ($now - $data['time'] > $time_window) {
            unset($_SESSION['rate_limit'][$k]);
        }
    }
    
    // ngecek current key
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [
            'count' => 1,
            'time' => $now
        ];
        return true;
    }
    
    if ($_SESSION['rate_limit'][$key]['count'] >= $max_attempts) {
        return false;
    }
    
    $_SESSION['rate_limit'][$key]['count']++;
    return true;
}
?>