<?php
require_once __DIR__ . '/../includes/security.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

try {
    $pdo = getDBConnection();

    // 1. Fetch Product Detail
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    // 2. Fetch Variants
    $vStmt = $pdo->prepare("SELECT id, size_label, price, stock FROM product_variants WHERE product_id = ? ORDER BY price ASC");
    $vStmt->execute([$productId]);
    $variants = $vStmt->fetchAll();

    // 3. Fetch Approved Reviews
    $rStmt = $pdo->prepare("
        SELECT r.id, r.rating, r.comment, r.before_image, r.after_image, r.created_at, u.name as user_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.product_id = ? AND r.approved = 1
        ORDER BY r.created_at DESC
    ");
    $rStmt->execute([$productId]);
    $reviews = $rStmt->fetchAll();

    // Format dates
    foreach ($reviews as &$r) {
        $r['created_at_formatted'] = date('M d, Y', strtotime($r['created_at']));
    }

    // 4. Fetch Related Products in same category
    $relStmt = $pdo->prepare("SELECT id, name, price, image, rating_avg FROM products WHERE category = ? AND id != ? LIMIT 4");
    $relStmt->execute([$product['category'], $productId]);
    $related = $relStmt->fetchAll();

    $product['variants'] = $variants;
    $product['reviews']  = $reviews;
    $product['related']  = $related;

    echo json_encode(['success' => true, 'product' => $product]);
} catch (Exception $e) {
    // Detail sirf server log mein; client ko generic message
    echo json_encode(['success' => false, 'message' => gloy_public_error_message($e, 'get_product failed')]);
}
