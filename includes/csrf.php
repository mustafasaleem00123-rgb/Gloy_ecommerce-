<?php
/**
 * Gloy Skincare - CSRF protection helpers
 */
require_once __DIR__ . '/security.php'; // session + security bootstrap

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Hidden input for <form> tags
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_valid() {
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
}

// Har POST action se pehle call karein. Token galat ho to request ruk jati hai.
function csrf_require() {
    if (csrf_valid()) {
        return;
    }
    $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
    if (!headers_sent()) {
        http_response_code(403);
    }
    if ($isAjax) {
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh the page and try again.']);
        exit;
    }
    echo '<div style="max-width:480px;margin:60px auto;padding:24px;border:1px solid #FECACA;background:#FEF2F2;color:#991B1B;border-radius:12px;font-family:sans-serif;text-align:center;">'
       . '<strong>Security check failed.</strong><br>Your session expired or the request was invalid. '
       . 'Please go back, refresh the page and try again.<br><br>'
       . '<a href="javascript:history.back()" style="color:#991B1B;font-weight:600;">&larr; Go back</a></div>';
    exit;
}
