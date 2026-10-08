<?php
require_once __DIR__ . '/../includes/security.php';
/**
 * Gloy Skincare - Instant Live Search Suggestions API
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['search']) ? trim($_GET['search']) : '');

if (empty($query) || strlen($query) < 2) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT id, name, slug, price, image, category, skin_type, rating_avg
        FROM products
        WHERE name LIKE :q OR description LIKE :q OR category LIKE :q OR ingredients LIKE :q OR subtitle LIKE :q
        ORDER BY is_bestseller DESC, price ASC
        LIMIT 5
    ");
    $stmt->execute([':q' => '%' . $query . '%']);
    $results = $stmt->fetchAll();

    foreach ($results as &$r) {
        $r['formatted_price'] = 'Rs. ' . number_format($r['price']);
    }

    echo json_encode(['success' => true, 'results' => $results]);
} catch (Exception $e) {
    // Detail sirf server log mein; client ko generic message
    echo json_encode(['success' => false, 'message' => gloy_public_error_message($e, 'search_suggestions failed')]);
}
