<?php
require_once __DIR__ . '/../includes/security.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address (e.g. name@gmail.com).']);
    exit;
}

$emailParts = explode('@', $email);
if (count($emailParts) !== 2) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
    exit;
}

$localPart = $emailParts[0];
$domainPart = $emailParts[1];

// Local username must contain letters (pure numeric like 123@gmail.com is disallowed)
if (!preg_match('/[a-zA-Z]/', $localPart)) {
    echo json_encode(['success' => false, 'message' => 'Email username cannot be purely numbers. It must contain letters (e.g. ayesha@gmail.com or ayesha123@gmail.com).']);
    exit;
}

if (!preg_match('/[a-zA-Z]/', $domainPart)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address with a valid domain.']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Auto-create newsletter_subscribers table if missing
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }

    // Check if email is already subscribed
    $chk = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
    $chk->execute([$email]);
    $existing = $chk->fetch();

    if (!$existing) {
        $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
        $stmt->execute([$email]);
        echo json_encode(['success' => true, 'message' => 'Thank you for subscribing to Gloy Skincare newsletter!']);
    } else {
        echo json_encode(['success' => true, 'message' => 'You are already subscribed to Gloy Skincare newsletter!']);
    }

} catch (Exception $e) {
    // Detail sirf server log mein; client ko generic message
    echo json_encode(['success' => false, 'message' => gloy_public_error_message($e, 'newsletter_signup failed')]);
}
