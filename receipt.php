<?php
/**
 * Gloy Skincare - Payment receipt viewer
 *
 * Payment screenshots ab URL se seedha nahi khulti (assets/uploads/ band hai).
 * Yeh file unhein sirf in ko dikhati hai:
 *   - admin
 *   - woh customer jis ka yeh order hai (logged in), ya wohi browser session jis ne abhi order diya
 */
require_once __DIR__ . '/includes/auth_middleware.php';

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    http_response_code(404);
    exit('Not found');
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT id, user_id, payment_screenshot FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order || empty($order['payment_screenshot'])) {
    http_response_code(404);
    exit('Not found');
}

$allowed = false;
if (is_admin()) {
    $allowed = true;
} elseif (is_logged_in() && !empty($order['user_id']) && (int)$order['user_id'] === (int)$_SESSION['user_id']) {
    $allowed = true;
} elseif (isset($_SESSION['last_order_id']) && (int)$_SESSION['last_order_id'] === $orderId) {
    $allowed = true;
}

if (!$allowed) {
    // 404 (403 nahi), taake order ids guess karne walon ko kuch pata na chale
    http_response_code(404);
    exit('Not found');
}

// basename(): path traversal se bachao
$path = __DIR__ . '/assets/uploads/payments/' . basename($order['payment_screenshot']);
if (!is_file($path)) {
    http_response_code(404);
    exit('Not found');
}

$info = @getimagesize($path);
$mimes = [IMAGETYPE_JPEG => 'image/jpeg', IMAGETYPE_PNG => 'image/png', IMAGETYPE_WEBP => 'image/webp'];
if ($info === false || !isset($mimes[$info[2]])) {
    http_response_code(404);
    exit('Not found');
}

// Session lock chhor dein taake bari file stream hote hue baqi requests na rukein
session_write_close();

header('Content-Type: ' . $mimes[$info[2]]);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'none\'; img-src \'self\'; style-src \'unsafe-inline\'');
header('Cache-Control: private, no-store');
readfile($path);
exit;
