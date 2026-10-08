<?php
/**
 * Gloy Skincare - Authentication Middleware & Helper Functions
 */

require_once __DIR__ . '/security.php'; // session + security bootstrap

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/csrf.php';

// Helper function to safely escape HTML entities
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Helper function to format prices in PKR
if (!function_exists('formatPrice')) {
    function formatPrice($amount) {
        return 'Rs. ' . number_format((float)$amount, 0);
    }
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function require_login($redirect_url = 'login.php') {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: $redirect_url");
        exit;
    }
}

function require_admin($redirect_url = '../admin/login.php') {
    if (!is_admin()) {
        header("Location: $redirect_url");
        exit;
    }
}

function get_current_user_data() {
    if (!is_logged_in()) return null;
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, name, email, phone, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
