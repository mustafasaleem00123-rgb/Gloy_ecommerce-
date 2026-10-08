<?php
/**
 * Gloy Skincare - Review Submission API with Photo Upload Support
 *
 * Rules:
 *  - Sirf logged-in customer review de sakta hai (koi bhi email se naya account nahi banta)
 *  - Naya review approved = 0 se shuru hota hai; admin approve karta hai tab site par dikhta hai
 *  - Photo uploads: content se image check (getimagesize), random filename, folder 0755
 *  - Optional: .env mein REVIEW_REQUIRE_PURCHASE=true ho to sirf woh log jinhon ne woh product khareeda ho
 */
require_once __DIR__ . '/../includes/security.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

function review_json($success, $message, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    review_json(false, 'Invalid request.', 405);
}

if (!csrf_valid()) {
    review_json(false, 'Security token expired. Please refresh the page and try again.', 403);
}

if (!is_logged_in()) {
    review_json(false, 'Please log in to submit a review.', 401);
}

$pdo = getDBConnection();
$userId = (int)$_SESSION['user_id'];

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating    = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
$comment   = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($productId <= 0 || mb_strlen($comment) < 5) {
    review_json(false, 'Please provide a detailed review (minimum 5 characters).');
}
if (mb_strlen($comment) > 2000) {
    review_json(false, 'Your review is too long (maximum 2000 characters).');
}
$rating = max(1, min(5, $rating));

try {
    // Product maujood hai?
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    if (!$stmt->fetch()) {
        review_json(false, 'Product not found.', 404);
    }

    // Optional: verified purchase
    if (strtolower((string)gloy_env('REVIEW_REQUIRE_PURCHASE', 'false')) === 'true') {
        $buy = $pdo->prepare("
            SELECT COUNT(*) FROM orders o
            JOIN order_items oi ON oi.order_id = o.id
            WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status != 'Cancelled'
        ");
        $buy->execute([$userId, $productId]);
        if ((int)$buy->fetchColumn() === 0) {
            review_json(false, 'Only customers who purchased this product can review it.', 403);
        }
    }

    // Ek user ek product par ek hi review (spam rokne ke liye)
    $dup = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ? AND product_id = ?");
    $dup->execute([$userId, $productId]);
    if ((int)$dup->fetchColumn() > 0) {
        review_json(false, 'You have already reviewed this product. Thank you!');
    }

    // Photo uploads (Before & After)
    $uploadDir = __DIR__ . '/../assets/images/reviews/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $savedFiles = [];
    $processUpload = function ($fileKey, $prefix) use ($uploadDir, &$savedFiles) {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $file = $_FILES[$fileKey];
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new GloyUserException('Image upload failed. Please try again with a smaller JPG, PNG, or WebP photo.');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new GloyUserException('Your photo is too large. Max size is 5MB.');
        }

        // Asli image: file ke content se check (extension / browser MIME par bharosa nahi)
        $info = @getimagesize($file['tmp_name']);
        $extByType = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
        if ($info === false || !isset($extByType[$info[2]])) {
            throw new GloyUserException('Invalid file format. Only real JPG, PNG, and WebP images are allowed.');
        }
        if ($info[0] > 8000 || $info[1] > 8000) {
            throw new GloyUserException('Image dimensions are too large. Please upload a smaller photo.');
        }

        // Extension hamesha detected type se, user ke diye naam se nahi
        $filename = $prefix . '_' . bin2hex(random_bytes(12)) . '.' . $extByType[$info[2]];
        $targetPath = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new GloyUserException('Failed to save uploaded image. Please try again.');
        }
        @chmod($targetPath, 0644);
        $savedFiles[] = $targetPath;
        return 'reviews/' . $filename;
    };

    try {
        $beforeImage = $processUpload('before_image', 'before');
        $afterImage  = $processUpload('after_image', 'after');

        // approved = 0: admin approve karega
        $insStmt = $pdo->prepare("
            INSERT INTO reviews (product_id, user_id, rating, comment, before_image, after_image, approved)
            VALUES (?, ?, ?, ?, ?, ?, 0)
        ");
        $insStmt->execute([$productId, $userId, $rating, $comment, $beforeImage, $afterImage]);
    } catch (Throwable $inner) {
        foreach ($savedFiles as $f) { @unlink($f); }
        throw $inner;
    }

    // Rating average/count yahan update NAHI hota: sirf approved reviews ginay jate hain
    // (admin/reviews.php approve karte waqt recalculate karta hai).
    review_json(true, 'Thank you! Your review has been submitted and will appear on the site once our team approves it.');

} catch (Throwable $e) {
    review_json(false, gloy_public_error_message($e, 'submit_review failed', 'We could not submit your review right now. Please try again later.'), $e instanceof GloyUserException ? 400 : 500);
}
