<?php
require_once __DIR__ . '/../includes/security.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$name    = isset($_POST['name']) ? trim($_POST['name']) : '';
$email   = isset($_POST['email']) ? trim($_POST['email']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validation
if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

// 1. Name Validation: Letters and spaces only (strictly NO numbers / digits)
if (preg_match('/[0-9]/', $name)) {
    echo json_encode(['success' => false, 'message' => 'Name cannot contain numbers. Please enter alphabetic letters only.']);
    exit;
}

if (strlen($name) < 3 || strlen($name) > 60 || !preg_match("/^[a-zA-Z\s\.\'\-]+$/u", $name)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid name (3 to 60 alphabetic characters).']);
    exit;
}

// 2. Email Validation: Proper email structure with alphabetic domain & valid TLD
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address (e.g. name@domain.com).']);
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

// 3. Subject Validation
if (strlen($subject) < 3 || strlen($subject) > 150) {
    echo json_encode(['success' => false, 'message' => 'Subject must be between 3 and 150 characters long.']);
    exit;
}

// 4. Message Validation
if (strlen($message) < 10 || strlen($message) > 3000) {
    echo json_encode(['success' => false, 'message' => 'Message must be between 10 and 3000 characters long.']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Auto-create messages table on connected database (MySQL / SQLite) if missing
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `messages` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `email` VARCHAR(150) NOT NULL,
                `subject` VARCHAR(200) NOT NULL,
                `message` TEXT NOT NULL,
                `is_read` TINYINT(1) DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                subject TEXT NOT NULL,
                message TEXT NOT NULL,
                is_read INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }

    $stmt = $pdo->prepare("INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $subject, $message]);

    echo json_encode(['success' => true, 'message' => 'Thank you for reaching out! Our team will respond shortly.']);
} catch (Exception $e) {
    // Detail sirf server log mein; client ko generic message
    echo json_encode(['success' => false, 'message' => gloy_public_error_message($e, 'submit_contact failed')]);
}
