<?php
/**
 * Wishlist API - Add/Remove/Toggle products in wishlist
 */
require_once __DIR__ . '/../includes/security.php'; // session + security bootstrap
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$pdo = getDBConnection();
$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : (isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0);

// Get user or session ID
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$sessionId = session_id();

try {
    if ($action === 'get_count') {
        if ($userId) {
            $countStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM wishlist WHERE user_id = ? OR session_id = ?");
            $countStmt->execute([$userId, $sessionId]);
        } else {
            $countStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM wishlist WHERE session_id = ?");
            $countStmt->execute([$sessionId]);
        }
        $count = (int)$countStmt->fetchColumn();
        echo json_encode(['success' => true, 'count' => $count]);
        exit;
    }

    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }

    // Verify product exists
    $chkP = $pdo->prepare("SELECT id, name FROM products WHERE id = ?");
    $chkP->execute([$productId]);
    $prod = $chkP->fetch();
    if (!$prod) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    if ($action === 'toggle') {
        // Check if item exists in wishlist
        if ($userId) {
            $checkStmt = $pdo->prepare("SELECT id FROM wishlist WHERE product_id = ? AND (user_id = ? OR session_id = ?)");
            $checkStmt->execute([$productId, $userId, $sessionId]);
        } else {
            $checkStmt = $pdo->prepare("SELECT id FROM wishlist WHERE product_id = ? AND session_id = ?");
            $checkStmt->execute([$productId, $sessionId]);
        }
        $row = $checkStmt->fetch();

        if ($row) {
            // Remove
            $delStmt = $pdo->prepare("DELETE FROM wishlist WHERE id = ?");
            $delStmt->execute([$row['id']]);
            $inWishlist = false;
            $msg = 'Removed from your wishlist';
        } else {
            // Add
            $insStmt = $pdo->prepare("INSERT INTO wishlist (user_id, session_id, product_id) VALUES (?, ?, ?)");
            $insStmt->execute([$userId, $sessionId, $productId]);
            $inWishlist = true;
            $msg = 'Saved to your wishlist ❤️';
        }

        // Get updated count
        if ($userId) {
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ? OR session_id = ?");
            $countStmt->execute([$userId, $sessionId]);
        } else {
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE session_id = ?");
            $countStmt->execute([$sessionId]);
        }
        $newCount = (int)$countStmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'message' => $msg,
            'in_wishlist' => $inWishlist,
            'count' => $newCount
        ]);
        exit;

    } elseif ($action === 'remove') {
        if ($userId) {
            $delStmt = $pdo->prepare("DELETE FROM wishlist WHERE product_id = ? AND (user_id = ? OR session_id = ?)");
            $delStmt->execute([$productId, $userId, $sessionId]);
        } else {
            $delStmt = $pdo->prepare("DELETE FROM wishlist WHERE product_id = ? AND session_id = ?");
            $delStmt->execute([$productId, $sessionId]);
        }

        // Get updated count
        if ($userId) {
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ? OR session_id = ?");
            $countStmt->execute([$userId, $sessionId]);
        } else {
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE session_id = ?");
            $countStmt->execute([$sessionId]);
        }
        $newCount = (int)$countStmt->fetchColumn();

        echo json_encode(['success' => true, 'message' => 'Item removed from wishlist', 'count' => $newCount]);
        exit;

    } elseif ($action === 'check') {
        if ($userId) {
            $checkStmt = $pdo->prepare("SELECT id FROM wishlist WHERE product_id = ? AND (user_id = ? OR session_id = ?)");
            $checkStmt->execute([$productId, $userId, $sessionId]);
        } else {
            $checkStmt = $pdo->prepare("SELECT id FROM wishlist WHERE product_id = ? AND session_id = ?");
            $checkStmt->execute([$productId, $sessionId]);
        }
        $exists = ($checkStmt->fetch() !== false);
        echo json_encode(['success' => true, 'in_wishlist' => $exists]);
        exit;

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }

} catch (Exception $e) {
    // Detail sirf server log mein; client ko generic message
    echo json_encode(['success' => false, 'message' => gloy_public_error_message($e, 'wishlist_action failed')]);
}
