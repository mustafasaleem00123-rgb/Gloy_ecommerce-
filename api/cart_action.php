<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/cart_helper.php';

$action    = isset($_POST['action']) ? trim($_POST['action']) : '';
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$variantId = isset($_POST['variant_id']) && !empty($_POST['variant_id']) ? (int)$_POST['variant_id'] : null;
$qty       = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
$cartId    = isset($_POST['cart_id']) ? $_POST['cart_id'] : null;

try {
    $resultData = [];

    switch ($action) {
        case 'add':
            if ($productId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product selected']);
                exit;
            }
            $resultData = add_to_cart($productId, $variantId, $qty);
            break;

        case 'update':
            if ($cartId === null) {
                echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
                exit;
            }
            $resultData = update_cart_qty($cartId, $qty);
            break;

        case 'remove':
            if ($cartId === null) {
                echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
                exit;
            }
            $resultData = remove_from_cart($cartId);
            break;

        case 'get':
            $resultData = get_cart_items();
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            exit;
    }

    // Find specific line total if updating
    $itemTotal = 0;
    if ($cartId !== null && isset($resultData['items'])) {
        foreach ($resultData['items'] as $item) {
            if ((string)$item['cart_id'] === (string)$cartId) {
                $itemTotal = $item['line_total'];
                break;
            }
        }
    }

    echo json_encode([
        'success'     => true,
        'cart_count'  => $resultData['cart_count'],
        'subtotal'    => $resultData['subtotal'],
        'grand_total' => $resultData['grand_total'],
        'item_total'  => $itemTotal,
        'items'       => $resultData['items']
    ]);
} catch (Exception $e) {
    // Detail sirf server log mein; client ko generic message
    echo json_encode(['success' => false, 'message' => gloy_public_error_message($e, 'cart_action failed')]);
}
