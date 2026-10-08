<?php
/**
 * Aura Skincare - Cart Management Helper (Session & DB Sync)
 */

require_once __DIR__ . '/security.php'; // session + security bootstrap

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth_middleware.php';

// Initialize session cart array if not existing
if (!isset($_SESSION['guest_cart'])) {
    $_SESSION['guest_cart'] = [];
}

/**
 * Get all cart items (formatted with product & variant details)
 */
function get_cart_items() {
    $pdo = getDBConnection();
    $items = [];
    $subtotal = 0.0;

    if (is_logged_in()) {
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("
            SELECT c.id as cart_id, c.qty, p.id as product_id, p.name, p.price as base_price, p.image, p.stock as base_stock,
                   pv.id as variant_id, pv.size_label, pv.price as variant_price, pv.stock as variant_stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_variants pv ON c.variant_id = pv.id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $unitPrice = $row['variant_price'] !== null ? (float)$row['variant_price'] : (float)$row['base_price'];
            $lineTotal = $unitPrice * (int)$row['qty'];
            $subtotal += $lineTotal;

            $items[] = [
                'cart_id'      => $row['cart_id'],
                'product_id'   => $row['product_id'],
                'variant_id'   => $row['variant_id'],
                'name'         => $row['name'],
                'size_label'   => $row['size_label'],
                'image'        => $row['image'],
                'unit_price'   => $unitPrice,
                'qty'          => (int)$row['qty'],
                'line_total'   => $lineTotal,
                'max_stock'    => $row['variant_stock'] !== null ? (int)$row['variant_stock'] : (int)$row['base_stock']
            ];
        }
    } else {
        // Guest session cart
        foreach ($_SESSION['guest_cart'] as $key => $cartRow) {
            $stmt = $pdo->prepare("SELECT id, name, price, image, stock FROM products WHERE id = ?");
            $stmt->execute([$cartRow['product_id']]);
            $product = $stmt->fetch();
            if (!$product) continue;

            $variant = null;
            if (!empty($cartRow['variant_id'])) {
                $vStmt = $pdo->prepare("SELECT id, size_label, price, stock FROM product_variants WHERE id = ?");
                $vStmt->execute([$cartRow['variant_id']]);
                $variant = $vStmt->fetch();
            }

            $unitPrice = $variant ? (float)$variant['price'] : (float)$product['price'];
            $lineTotal = $unitPrice * (int)$cartRow['qty'];
            $subtotal += $lineTotal;

            $items[] = [
                'cart_id'      => $key, // Array key used as pseudo cart_id for guests
                'product_id'   => $product['id'],
                'variant_id'   => $variant ? $variant['id'] : null,
                'name'         => $product['name'],
                'size_label'   => $variant ? $variant['size_label'] : null,
                'image'        => $product['image'],
                'unit_price'   => $unitPrice,
                'qty'          => (int)$cartRow['qty'],
                'line_total'   => $lineTotal,
                'max_stock'    => $variant ? (int)$variant['stock'] : (int)$product['stock']
            ];
        }
    }

    return [
        'items'       => $items,
        'subtotal'    => $subtotal,
        'grand_total' => $subtotal, // Tax/shipping can be added
        'cart_count'  => array_sum(array_column($items, 'qty'))
    ];
}

/**
 * Add item to cart
 */
function add_to_cart($productId, $variantId = null, $qty = 1) {
    $pdo = getDBConnection();
    $qty = max(1, (int)$qty);

    if (is_logged_in()) {
        $userId = $_SESSION['user_id'];
        
        // Check if item already exists in DB cart
        if ($variantId) {
            $stmt = $pdo->prepare("SELECT id, qty FROM cart WHERE user_id = ? AND product_id = ? AND variant_id = ?");
            $stmt->execute([$userId, $productId, $variantId]);
        } else {
            $stmt = $pdo->prepare("SELECT id, qty FROM cart WHERE user_id = ? AND product_id = ? AND variant_id IS NULL");
            $stmt->execute([$userId, $productId]);
        }
        $existing = $stmt->fetch();

        if ($existing) {
            $newQty = $existing['qty'] + $qty;
            $uStmt = $pdo->prepare("UPDATE cart SET qty = ? WHERE id = ?");
            $uStmt->execute([$newQty, $existing['id']]);
        } else {
            $iStmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, variant_id, qty) VALUES (?, ?, ?, ?)");
            $iStmt->execute([$userId, $productId, $variantId ?: null, $qty]);
        }
    } else {
        // Guest cart session key: p{product_id}_v{variant_id}
        $key = "p{$productId}_v" . ($variantId ?: '0');
        if (isset($_SESSION['guest_cart'][$key])) {
            $_SESSION['guest_cart'][$key]['qty'] += $qty;
        } else {
            $_SESSION['guest_cart'][$key] = [
                'product_id' => $productId,
                'variant_id' => $variantId ?: null,
                'qty'        => $qty
            ];
        }
    }

    return get_cart_items();
}

/**
 * Update cart item quantity
 */
function update_cart_qty($cartId, $qty) {
    $pdo = getDBConnection();
    $qty = max(1, (int)$qty);

    if (is_logged_in()) {
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("UPDATE cart SET qty = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$qty, $cartId, $userId]);
    } else {
        if (isset($_SESSION['guest_cart'][$cartId])) {
            $_SESSION['guest_cart'][$cartId]['qty'] = $qty;
        }
    }

    return get_cart_items();
}

/**
 * Remove item from cart
 */
function remove_from_cart($cartId) {
    $pdo = getDBConnection();

    if (is_logged_in()) {
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cartId, $userId]);
    } else {
        if (isset($_SESSION['guest_cart'][$cartId])) {
            unset($_SESSION['guest_cart'][$cartId]);
        }
    }

    return get_cart_items();
}

/**
 * Merge Guest Session Cart into DB Cart upon User Login
 */
function merge_guest_cart_to_user($userId) {
    if (empty($_SESSION['guest_cart'])) return;
    $pdo = getDBConnection();

    foreach ($_SESSION['guest_cart'] as $item) {
        $productId = $item['product_id'];
        $variantId = $item['variant_id'];
        $qty       = $item['qty'];

        if ($variantId) {
            $stmt = $pdo->prepare("SELECT id, qty FROM cart WHERE user_id = ? AND product_id = ? AND variant_id = ?");
            $stmt->execute([$userId, $productId, $variantId]);
        } else {
            $stmt = $pdo->prepare("SELECT id, qty FROM cart WHERE user_id = ? AND product_id = ? AND variant_id IS NULL");
            $stmt->execute([$userId, $productId]);
        }
        $existing = $stmt->fetch();

        if ($existing) {
            $newQty = $existing['qty'] + $qty;
            $uStmt = $pdo->prepare("UPDATE cart SET qty = ? WHERE id = ?");
            $uStmt->execute([$newQty, $existing['id']]);
        } else {
            $iStmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, variant_id, qty) VALUES (?, ?, ?, ?)");
            $iStmt->execute([$userId, $productId, $variantId ?: null, $qty]);
        }
    }

    // Clear guest cart
    $_SESSION['guest_cart'] = [];
}
