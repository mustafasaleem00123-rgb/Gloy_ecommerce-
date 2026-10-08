<?php
$pageTitle = 'Your Shopping Cart';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/cart_helper.php';

$cartData = get_cart_items();
$items = $cartData['items'];
$subtotal = $cartData['subtotal'];
?>

<div class="container section">
  <h1 style="font-size: 2.8rem; margin-bottom: 24px; color:var(--text-heading);">Shopping Cart</h1>

  <div id="cart-wrapper">
    <?php if (empty($items)): ?>
      <div style="text-align:center; padding: 60px 0; background:var(--bg-surface); border-radius:var(--radius-md); border:1px solid var(--border-color);">
        <div style="margin-bottom:16px;"><i class="bi bi-bag-x" style="font-size:3.5rem; color:var(--primary-purple);"></i></div>
        <h3 style="margin-bottom:12px; color:var(--text-heading);">Your shopping cart is currently empty</h3>
        <p style="color:var(--text-muted); margin-bottom:24px;">Discover our botanical skincare collection to add items.</p>
        <a href="products.php" class="btn btn-primary">Start Shopping</a>
      </div>
    <?php else: ?>
      <div class="cart-page-grid" style="display:grid; grid-template-columns: 2fr 1fr; gap:36px; align-items:start;">
        
        <!-- Cart Items Table Container -->
        <div class="cart-table-wrapper" style="background:var(--bg-surface); border-radius:var(--radius-md); border:1px solid var(--border-color); overflow-x:auto;">
          <table class="data-table" style="width:100%; min-width:540px;">
            <thead>
              <tr>
                <th style="padding:18px 24px;">Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="cart-table-body">
              <?php foreach ($items as $item): ?>
                <tr data-cart-id="<?php echo e($item['cart_id']); ?>">
                  <td style="padding:18px 24px;">
                    <div style="display:flex; align-items:center; gap:16px;">
                      <img src="assets/images/<?php echo e($item['image']); ?>" alt="<?php echo e($item['name']); ?>" style="width:60px; height:60px; object-fit:contain; background:var(--bg-main); border-radius:var(--radius-sm); padding:6px;" loading="lazy">
                      <div>
                        <strong style="color:var(--text-heading); font-size:1rem; display:block;"><?php echo e($item['name']); ?></strong>
                        <?php if (!empty($item['size_label'])): ?>
                          <span style="font-size:0.8rem; color:var(--primary-purple); font-weight:600;"><?php echo e($item['size_label']); ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td style="font-weight:600; color:var(--text-heading);">
                    Rs. <?php echo number_format($item['unit_price']); ?>
                  </td>
                  <td>
                    <div class="qty-stepper">
                      <button type="button" class="qty-btn js-cart-qty-btn" data-change="-1">-</button>
                      <input type="number" class="qty-input cart-qty-val" value="<?php echo $item['qty']; ?>" readonly>
                      <button type="button" class="qty-btn js-cart-qty-btn" data-change="1">+</button>
                    </div>
                  </td>
                  <td style="font-family:var(--font-serif); font-weight:600; font-size:1.1rem; color:var(--text-heading);" class="cart-line-total">
                    Rs. <?php echo number_format($item['line_total']); ?>
                  </td>
                  <td style="text-align:right; padding-right:24px;">
                    <button type="button" class="icon-btn js-remove-cart-item" title="Remove item" style="color:var(--accent-terracotta);">
                      <i class="bi bi-trash3"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Order Summary Card -->
        <div style="background:var(--bg-surface); border-radius:var(--radius-md); border:1px solid var(--border-color); padding:28px;">
          <h3 style="font-size:1.4rem; margin-bottom:20px; border-bottom:1px solid var(--border-color); padding-bottom:14px; color:var(--text-heading);">Order Summary</h3>
          
          <div style="display:flex; justify-content:space-between; margin-bottom:14px; font-size:0.95rem;">
            <span style="color:var(--text-body);">Subtotal</span>
            <strong id="cart-subtotal" style="color:var(--text-heading);">Rs. <?php echo number_format($subtotal); ?></strong>
          </div>

          <div style="display:flex; justify-content:space-between; margin-bottom:14px; font-size:0.95rem; color:var(--text-muted);">
            <span>Estimated Shipping</span>
            <span style="color:var(--primary-purple); font-weight:600;">FREE</span>
          </div>

          <div style="display:flex; justify-content:space-between; margin-top:20px; padding-top:16px; border-top:2px dashed var(--border-color); font-size:1.2rem; font-weight:bold; color:var(--text-heading);">
            <span>Total</span>
            <span id="cart-grand-total" style="font-family:var(--font-serif); color:var(--text-heading);">Rs. <?php echo number_format($subtotal); ?></span>
          </div>

          <a href="checkout.php" class="btn btn-primary" style="width:100%; margin-top:24px;">
            Proceed to Checkout
          </a>

          <a href="https://wa.me/923168882788?text=<?php echo urlencode('Hi Gloy, I want to order items from my cart totaling Rs. ' . number_format($subtotal) . '. Delivery Address: ...'); ?>" target="_blank" class="btn btn-accent" style="width:100%; margin-top:12px;">
            <i class="bi bi-whatsapp me-1"></i> Quick Order via WhatsApp
          </a>

          <a href="products.php" style="display:block; text-align:center; margin-top:16px; font-size:0.85rem; color:var(--text-muted);">
            <i class="bi bi-arrow-left me-1"></i> Continue Shopping
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
