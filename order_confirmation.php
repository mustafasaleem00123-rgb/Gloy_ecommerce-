<?php
/**
 * Gloy Skincare - Order Confirmation & Post-Checkout Guest Account Activation
 */
require_once __DIR__ . '/includes/auth_middleware.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT o.*, p.transaction_id, p.status as payment_record_status FROM orders o LEFT JOIN payments p ON o.id = p.order_id WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit;
}

// ---- OWNERSHIP CHECK ----
// Order sirf in ko dikhta hai: (1) admin, (2) logged-in owner, (3) wohi browser session jis ne abhi order diya.
$ownsThisOrder = isset($_SESSION['last_order_id']) && (int)$_SESSION['last_order_id'] === (int)$orderId;
$isOrderOwner  = is_logged_in() && !empty($order['user_id']) && (int)$order['user_id'] === (int)$_SESSION['user_id'];

if (!is_admin() && !$isOrderOwner && !$ownsThisOrder) {
    http_response_code(403);
    $pageTitle = 'Access Denied';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="container section">
      <div style="max-width:520px; margin:0 auto; background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:40px; box-shadow:var(--shadow-md); text-align:center;">
        <h1 style="font-size:1.8rem; margin-bottom:10px; color:var(--text-heading);">Access Denied</h1>
        <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:24px;">You do not have permission to view this order. Please log in with the account that placed it.</p>
        <a href="login.php" class="btn btn-primary">Log In</a>
        <a href="index.php" class="btn btn-secondary" style="margin-left:8px;">Back to Home</a>
      </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch Order Items
$itemStmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image, pv.size_label
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_variants pv ON oi.variant_id = pv.id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$orderId]);
$orderItems = $itemStmt->fetchAll();

// Handle Post-Checkout Guest Account Activation
$accountMsg = '';
$accountError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'activate_guest_account') {
    csrf_require();
    $guestPassword = $_POST['password'] ?? '';

    if (is_logged_in() || !empty($order['user_id']) || !$ownsThisOrder) {
        $accountError = 'This order cannot be used to create an account.';
    } elseif (empty($guestPassword) || strlen($guestPassword) < 8) {
        $accountError = 'Password must be at least 8 characters.';
    } else {
        try {
            $chkUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $chkUser->execute([$order['email']]);

            if ($chkUser->fetch()) {
                // Email pehle se registered hai: kabhi auto-login nahi, user ko login karna hoga
                $accountError = 'An account with this email already exists. Please log in instead.';
            } else {
                $hash = password_hash($guestPassword, PASSWORD_BCRYPT);
                $ins = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'user')");
                $ins->execute([$order['name'], $order['email'], $order['phone'], $hash]);
                $userId = $pdo->lastInsertId();

                $addrIns = $pdo->prepare("INSERT INTO user_addresses (user_id, address_line, city, postal_code, phone, is_default) VALUES (?, ?, ?, ?, ?, 1)");
                $addrIns->execute([$userId, $order['address'], $order['city'], $order['postal_code'], $order['phone']]);

                $upd = $pdo->prepare("UPDATE orders SET user_id = ? WHERE id = ?");
                $upd->execute([$userId, $orderId]);

                gloy_login_user(['id' => $userId, 'name' => $order['name'], 'email' => $order['email'], 'role' => 'user']);

                $accountMsg = 'Account activated successfully! You are now logged in and this order is saved in your account dashboard.';
                $order['user_id'] = $userId;
            }
        } catch (Exception $ex) {
            gloy_log_error('activate_guest_account error', $ex);
            $accountError = 'Could not create account. Please try again.';
        }
    }
}

$currentUser = get_current_user_data();

$pageTitle = 'Order Confirmed #' . $order['order_number'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container section">
  <div style="max-width:780px; margin:0 auto; background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:48px; box-shadow:var(--shadow-md);">
    
    <!-- Header Badge -->
    <div style="text-align:center; margin-bottom:36px;">
      <div style="width:72px; height:72px; border-radius:50%; background:rgba(99,102,241,0.12); color:var(--primary-purple); font-size:2.4rem; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
        <i class="bi bi-check-lg"></i>
      </div>
      <h1 style="font-size:2.3rem; margin-bottom:8px; color:var(--text-heading);">Thank You for Your Order!</h1>
      <p style="color:var(--text-muted); font-size:1.05rem;">
        Order Reference: <strong style="color:var(--primary-purple);"><?php echo e($order['order_number']); ?></strong>
      </p>
      <span style="font-size:0.85rem; color:var(--text-muted);">
        A confirmation has been recorded for <strong><?php echo e($order['email']); ?></strong>
      </span>
    </div>

    <!-- Post-Checkout 1-Click Guest Account Activation Card -->
    <?php if (!$currentUser && empty($order['user_id'])): ?>
      <div style="background:var(--bg-main); border:1.5px solid var(--primary-purple); border-radius:var(--radius-md); padding:24px; margin-bottom:32px;">
        <?php if ($accountMsg): ?>
          <div style="color:var(--primary-purple); font-weight:600; font-size:0.95rem;">
            <i class="bi bi-check-circle-fill me-1"></i> <?php echo e($accountMsg); ?>
          </div>
        <?php else: ?>
          <div style="display:flex; align-items:flex-start; gap:16px;">
            <div style="width:40px; height:40px; border-radius:50%; background:var(--primary-purple); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
              <i class="bi bi-person-plus-fill"></i>
            </div>
            <div style="flex:1;">
              <h4 style="font-size:1.1rem; color:var(--text-heading); margin:0 0 6px 0;">Save your info for faster checkout &amp; live order tracking?</h4>
              <p style="font-size:0.85rem; color:var(--text-muted); margin:0 0 16px 0;">
                Create a password now to link this order to your new Gloy account.
              </p>

              <?php if ($accountError): ?>
                <div style="color:var(--accent-terracotta); font-size:0.85rem; margin-bottom:12px;">
                  <i class="bi bi-exclamation-circle me-1"></i> <?php echo e($accountError); ?>
                </div>
              <?php endif; ?>

              <form method="POST" action="order_confirmation.php?id=<?php echo $orderId; ?>" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="activate_guest_account">
                <div class="password-toggle-wrap" style="flex:1; min-width:200px;">
                  <input type="password" name="password" id="order-confirm-password" class="input-field" placeholder="Create Password (min 8 chars)" style="padding:8px 14px; font-size:0.9rem;" required>
                  <button type="button" class="password-toggle-btn js-toggle-password" data-target="order-confirm-password" aria-label="Toggle password visibility">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="padding:10px 20px; font-weight:700;">
                  <i class="bi bi-shield-check me-1"></i> Create Account
                </button>
              </form>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Easypaisa Verification Status Banner -->
    <?php if ($order['payment_method'] === 'Easypaisa'): ?>
      <div style="background:linear-gradient(135deg, rgba(16,185,129,0.1), rgba(16,185,129,0.03)); border:1.5px solid #10B981; border-radius:var(--radius-md); padding:20px; margin-bottom:28px;">
        <div style="display:flex; align-items:flex-start; gap:14px;">
          <i class="bi bi-patch-check-fill" style="font-size:1.8rem; color:#10B981; flex-shrink:0;"></i>
          <div>
            <h4 style="font-size:1.1rem; color:var(--text-heading); margin:0 0 6px 0;">Easypaisa Payment Received — Verification in Progress</h4>
            <p style="font-size:0.88rem; color:var(--text-body); margin:0 0 8px 0; line-height:1.5;">
              Thank you! We have received your Easypaisa payment details<?php echo !empty($order['transaction_id']) ? " (<strong>TRX ID: " . e($order['transaction_id']) . "</strong>)" : ""; ?>. 
              Our team is verifying the transfer to <strong>03132775608 (Iqra Sohail)</strong>. Once verified, your order will be packed and dispatched immediately.
            </p>
            <?php if (!empty($order['payment_screenshot'])): ?>
              <div style="display:inline-flex; align-items:center; gap:8px; background:#fff; border:1px solid rgba(16,185,129,0.3); border-radius:6px; padding:6px 12px; margin-top:4px; font-size:0.8rem; color:#059669;">
                <i class="bi bi-image"></i> Receipt Uploaded: 
                <a href="receipt.php?id=<?php echo (int)$order['id']; ?>" target="_blank" style="color:#10B981; font-weight:700; text-decoration:underline;">View Receipt Proof</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Order Metadata Summary Grid -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; padding:24px; background:var(--bg-main); border-radius:var(--radius-md); margin-bottom:32px; font-size:0.92rem;">
      <div>
        <span style="color:var(--text-muted); display:block; margin-bottom:4px; font-size:0.8rem; text-transform:uppercase;">Delivery Address</span>
        <strong style="color:var(--text-heading); display:block; font-size:1rem;"><?php echo e($order['name']); ?></strong>
        <p style="color:var(--text-body); font-size:0.88rem; margin:4px 0 0 0; line-height:1.5;">
          <?php echo e($order['address']); ?><br>
          <?php echo e($order['city']); ?>, <?php echo e($order['postal_code']); ?><br>
          Phone: <?php echo e($order['phone']); ?>
        </p>
      </div>

      <div>
        <span style="color:var(--text-muted); display:block; margin-bottom:4px; font-size:0.8rem; text-transform:uppercase;">Payment Details</span>
        <strong style="color:var(--text-heading); display:block; font-size:1rem;">
          <?php echo e($order['payment_method']); ?> Gateway
        </strong>
        <div style="margin-top:8px;">
          <span class="status-badge <?php echo in_array($order['payment_status'], ['Paid', 'Completed']) ? 'status-paid' : 'status-pending'; ?>" style="<?php echo $order['payment_status'] === 'Pending Verification' ? 'background:rgba(217,119,6,0.15); color:#D97706; border:1px solid rgba(217,119,6,0.3);' : ''; ?>">
            <?php echo e($order['payment_status']); ?>
          </span>
        </div>
        <?php if ($order['payment_method'] === 'Easypaisa'): ?>
          <div style="font-size:0.78rem; color:var(--text-body); margin-top:8px; background:var(--bg-surface); padding:6px 10px; border-radius:6px; border:1px solid var(--border-color);">
            Receiver: <strong>03132775608</strong> (Iqra Sohail)
          </div>
        <?php endif; ?>
        <?php if (!empty($order['transaction_id'])): ?>
          <div style="font-size:0.75rem; color:var(--text-muted); margin-top:6px; font-family:monospace;">
            Tx ID: <strong><?php echo e($order['transaction_id']); ?></strong>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Order Items List -->
    <h3 style="font-size:1.2rem; margin-bottom:16px; border-bottom:1px solid var(--border-color); padding-bottom:10px; color:var(--text-heading);">Ordered Items</h3>
    <div style="margin-bottom:28px;">
      <?php foreach ($orderItems as $item): ?>
        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px dashed var(--border-color);">
          <div style="display:flex; align-items:center; gap:16px;">
            <img src="assets/images/<?php echo e($item['image']); ?>" alt="<?php echo e($item['name']); ?>" style="width:50px; height:50px; object-fit:contain; background:var(--bg-main); border-radius:var(--radius-sm); padding:4px;">
            <div>
              <strong style="font-size:0.95rem; color:var(--text-heading); display:block;"><?php echo e($item['name']); ?></strong>
              <span style="font-size:0.8rem; color:var(--text-muted);">
                Qty: <?php echo $item['qty']; ?> <?php echo $item['size_label'] ? "({$item['size_label']})" : ''; ?>
              </span>
            </div>
          </div>
          <strong style="font-size:1rem; color:var(--text-heading); font-family:var(--font-serif);">
            Rs. <?php echo number_format($item['price'] * $item['qty']); ?>
          </strong>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Total -->
    <div style="display:flex; justify-content:space-between; font-size:1.3rem; font-weight:bold; color:var(--text-heading); margin-bottom:36px; padding-top:12px; border-top:1px solid var(--border-color);">
      <span>Total Paid:</span>
      <span style="font-family:var(--font-serif); color:var(--primary-purple);">Rs. <?php echo number_format($order['total_amount']); ?></span>
    </div>

    <div style="display:flex; gap:16px; justify-content:center; flex-wrap:wrap;">
      <?php if (is_logged_in()): ?>
        <a href="account.php" class="btn btn-secondary"><i class="bi bi-box-seam me-1"></i> View in Account</a>
      <?php endif; ?>
      <a href="products.php" class="btn btn-primary"><i class="bi bi-bag me-1"></i> Continue Shopping</a>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
