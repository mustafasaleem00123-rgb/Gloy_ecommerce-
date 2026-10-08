<?php
require_once __DIR__ . '/includes/auth_middleware.php';

require_login('login.php?redirect=account.php');

$userId = $_SESSION['user_id'];
$pdo = getDBConnection();

$msg = '';
$error = '';
$activeTab = $_GET['tab'] ?? 'orders';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $actionType = $_POST['action_type'] ?? '';

    // 1. Update Profile
    if ($actionType === 'update_profile') {
        $activeTab = 'profile';
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $newPass = $_POST['new_password'] ?? '';

        if (!empty($phone)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($cleanPhone) > 11) {
                $error = 'Phone number cannot exceed 11 digits.';
            } else {
                $phone = $cleanPhone;
            }
        }

        if (empty($error)) {
            if (empty($name)) {
                $error = 'Name cannot be empty.';
            } else {
                if (!empty($newPass)) {
                    if (strlen($newPass) < 8) {
                        $error = 'New password must be at least 8 characters.';
                    } else {
                        $hash = password_hash($newPass, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, password_hash = ? WHERE id = ?");
                        $stmt->execute([$name, $phone, $hash, $userId]);
                        $msg = 'Profile and password updated successfully.';
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
                    $stmt->execute([$name, $phone, $userId]);
                    $msg = 'Profile updated successfully.';
                }
                $_SESSION['user_name'] = $name;
            }
        }
    }

    // 2. Add New Address
    elseif ($actionType === 'add_address') {
        $activeTab = 'addresses';
        $addressLine = trim($_POST['address_line'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $isDefault = isset($_POST['is_default']) ? 1 : 0;

        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($addressLine)) {
            $error = 'Street / Delivery address is required.';
        } elseif (empty($city)) {
            $error = 'City is required.';
        } elseif (empty($cleanPhone) || strlen($cleanPhone) !== 11 || !str_starts_with($cleanPhone, '03')) {
            $error = 'Please enter a valid 11-digit mobile number starting with 03 (e.g. 03168882788).';
        } else {
            $phone = $cleanPhone;
            // If user has 0 addresses, first one is auto default
            $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
            $cntStmt->execute([$userId]);
            $existingCount = (int)$cntStmt->fetchColumn();
            if ($existingCount === 0) {
                $isDefault = 1;
            }

            if ($isDefault === 1) {
                $resetStmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
                $resetStmt->execute([$userId]);
            }

            $insStmt = $pdo->prepare("
                INSERT INTO user_addresses (user_id, address_line, city, postal_code, phone, is_default)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insStmt->execute([$userId, $addressLine, $city, $postalCode, $phone, $isDefault]);
            $msg = 'Delivery address saved successfully.';
        }
    }

    // 3. Edit Address
    elseif ($actionType === 'edit_address') {
        $activeTab = 'addresses';
        $addressId = (int)($_POST['address_id'] ?? 0);
        $addressLine = trim($_POST['address_line'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $isDefault = isset($_POST['is_default']) ? 1 : 0;

        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($addressLine) || empty($city)) {
            $error = 'Address and city are required.';
        } elseif (empty($cleanPhone) || strlen($cleanPhone) !== 11 || !str_starts_with($cleanPhone, '03')) {
            $error = 'Please enter a valid 11-digit mobile number starting with 03 (e.g. 03168882788).';
        } else {
            $phone = $cleanPhone;
            // Verify address belongs to user
            $chkStmt = $pdo->prepare("SELECT id FROM user_addresses WHERE id = ? AND user_id = ?");
            $chkStmt->execute([$addressId, $userId]);
            if ($chkStmt->fetch()) {
                if ($isDefault === 1) {
                    $resetStmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
                    $resetStmt->execute([$userId]);
                }

                $updStmt = $pdo->prepare("
                    UPDATE user_addresses 
                    SET address_line = ?, city = ?, postal_code = ?, phone = ?, is_default = ?
                    WHERE id = ? AND user_id = ?
                ");
                $updStmt->execute([$addressLine, $city, $postalCode, $phone, $isDefault, $addressId, $userId]);
                $msg = 'Delivery address updated successfully.';
            } else {
                $error = 'Address not found or unauthorized.';
            }
        }
    }

    // 4. Delete Address
    elseif ($actionType === 'delete_address') {
        $activeTab = 'addresses';
        $addressId = (int)($_POST['address_id'] ?? 0);

        $chkStmt = $pdo->prepare("SELECT is_default FROM user_addresses WHERE id = ? AND user_id = ?");
        $chkStmt->execute([$addressId, $userId]);
        $addrRow = $chkStmt->fetch();

        if ($addrRow) {
            $wasDefault = (int)$addrRow['is_default'];
            $delStmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
            $delStmt->execute([$addressId, $userId]);

            // If deleted address was default, make another one default
            if ($wasDefault === 1) {
                $nextStmt = $pdo->prepare("SELECT id FROM user_addresses WHERE user_id = ? LIMIT 1");
                $nextStmt->execute([$userId]);
                $nextId = $nextStmt->fetchColumn();
                if ($nextId) {
                    $updDef = $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ?");
                    $updDef->execute([$nextId]);
                }
            }
            $msg = 'Address removed successfully.';
        }
    }

    // 5. Set As Default Address
    elseif ($actionType === 'set_default_address') {
        $activeTab = 'addresses';
        $addressId = (int)($_POST['address_id'] ?? 0);

        $resetStmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
        $resetStmt->execute([$userId]);

        $setStmt = $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $setStmt->execute([$addressId, $userId]);
        $msg = 'Default shipping address updated.';
    }
}

// Fetch Current User Data
$user = get_current_user_data();

// Fetch Saved Addresses
$addrStmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$addrStmt->execute([$userId]);
$addresses = $addrStmt->fetchAll();

// Fetch Order History
$ordStmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$ordStmt->execute([$userId]);
$orders = $ordStmt->fetchAll();

// Fetch items for each order
foreach ($orders as &$order) {
    $itemStmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image, pv.size_label
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_variants pv ON oi.variant_id = pv.id
        WHERE oi.order_id = ?
    ");
    $itemStmt->execute([$order['id']]);
    $order['items'] = $itemStmt->fetchAll();
}

$pageTitle = 'My Account | Gloy Skincare';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container section">
  <div style="margin-bottom:36px;">
    <h1 style="font-size:2.8rem; margin-bottom:8px; color:var(--text-heading);">My Account</h1>
    <p style="color:var(--text-body);">Manage your personal profile, delivery addresses, and track your Gloy skincare order history.</p>
  </div>

  <?php if ($msg): ?>
    <div style="background:var(--bg-accent-sage); color:var(--primary-purple); border:1.5px solid var(--primary-purple); padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; font-weight:600; display:flex; align-items:center; gap:10px;">
      <i class="bi bi-check-circle-fill" style="font-size:1.2rem;"></i> <?php echo e($msg); ?>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div style="background:var(--accent-blush); color:var(--accent-terracotta); border:1.5px solid var(--accent-terracotta); padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; font-weight:600; display:flex; align-items:center; gap:10px;">
      <i class="bi bi-exclamation-triangle-fill" style="font-size:1.2rem;"></i> <?php echo e($error); ?>
    </div>
  <?php endif; ?>

  <div class="account-layout-grid" style="display:grid; grid-template-columns: 260px 1fr; gap:36px;">
    <!-- Account Nav -->
    <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px; height:fit-content; box-shadow:var(--shadow-sm);">
      <div style="text-align:center; padding-bottom:16px; margin-bottom:16px; border-bottom:1px solid var(--border-color);">
        <div style="width:60px; height:60px; border-radius:50%; background:var(--bg-accent-sage); color:var(--primary-purple); font-size:1.5rem; font-weight:700; display:flex; align-items:center; justify-content:center; margin:0 auto 10px;">
          <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
        </div>
        <strong style="color:var(--text-heading); font-size:1rem; display:block;"><?php echo e($user['name']); ?></strong>
        <span style="color:var(--text-muted); font-size:0.8rem;"><?php echo e($user['email']); ?></span>
      </div>

      <ul style="display:flex; flex-direction:column; gap:8px;">
        <li><button type="button" class="tab-btn <?php echo $activeTab === 'orders' ? 'active' : ''; ?>" data-tab="orders" style="width:100%; text-align:left; padding:10px 14px;"><i class="bi bi-box-seam me-2"></i> Order History</button></li>
        <li><button type="button" class="tab-btn <?php echo $activeTab === 'profile' ? 'active' : ''; ?>" data-tab="profile" style="width:100%; text-align:left; padding:10px 14px;"><i class="bi bi-person me-2"></i> Profile Details</button></li>
        <li><button type="button" class="tab-btn <?php echo $activeTab === 'addresses' ? 'active' : ''; ?>" data-tab="addresses" style="width:100%; text-align:left; padding:10px 14px;"><i class="bi bi-geo-alt me-2"></i> Saved Addresses <span style="background:var(--primary-purple); color:#fff; font-size:0.7rem; padding:2px 8px; border-radius:20px; margin-left:auto; float:right;"><?php echo count($addresses); ?></span></button></li>
        <li style="border-top:1px solid var(--border-color); margin-top:10px; padding-top:10px;">
          <a href="logout.php" style="color:var(--accent-terracotta); font-weight:600; display:block; padding:10px 14px; text-decoration:none;"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
        </li>
      </ul>
    </div>

    <!-- Main Content Tabs -->
    <div>
      <!-- TAB 1: ORDER HISTORY -->
      <div class="tab-content <?php echo $activeTab === 'orders' ? 'active' : ''; ?>" id="tab-orders">
        <h3 style="font-size:1.5rem; margin-bottom:20px; color:var(--text-heading);">Order History (<?php echo count($orders); ?>)</h3>

        <?php if (empty($orders)): ?>
          <div style="background:var(--bg-surface); padding:40px; text-align:center; border-radius:var(--radius-md); border:1px solid var(--border-color);">
            <div style="font-size:2.5rem; color:var(--text-muted); margin-bottom:12px;"><i class="bi bi-bag"></i></div>
            <p style="color:var(--text-muted); margin-bottom:16px;">You haven't placed any orders yet.</p>
            <a href="products.php" class="btn btn-primary btn-sm">Explore Skincare Collection</a>
          </div>
        <?php else: ?>
          <div class="accordion-container" style="display:flex; flex-direction:column; gap:16px;">
            <?php foreach ($orders as $ord): ?>
              <div class="accordion-item" style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0 24px; overflow:hidden; box-shadow:var(--shadow-sm);">
                <button type="button" class="accordion-header" style="width:100%; display:flex; justify-content:space-between; align-items:center; padding:18px 0; background:none; border:none; cursor:pointer;">
                  <div style="display:flex; align-items:center; gap:20px; text-align:left;">
                    <div>
                      <strong style="color:var(--text-heading); display:block; font-size:1.05rem;"><?php echo e($ord['order_number']); ?></strong>
                      <span style="font-size:0.8rem; color:var(--text-muted); font-family:var(--font-sans);"><?php echo date('M d, Y', strtotime($ord['created_at'])); ?></span>
                    </div>

                    <span class="status-badge <?php 
                      if ($ord['order_status'] === 'Delivered') echo 'status-delivered';
                      elseif ($ord['order_status'] === 'Shipped') echo 'status-shipped';
                      elseif ($ord['order_status'] === 'Cancelled') echo 'status-cancelled';
                      else echo 'status-pending';
                    ?>">
                      <?php echo e($ord['order_status']); ?>
                    </span>
                  </div>

                  <div style="display:flex; align-items:center; gap:20px;">
                    <strong style="font-size:1.15rem; font-family:var(--font-serif); color:var(--primary-purple);">
                      Rs. <?php echo number_format($ord['total_amount']); ?>
                    </strong>
                    <span class="accordion-icon"><i class="bi bi-chevron-down"></i></span>
                  </div>
                </button>

                <div class="accordion-body">
                  <div style="padding-top:10px; border-top:1px dashed var(--border-color); margin-bottom:14px; font-size:0.85rem; color:var(--text-muted);">
                    Payment: <strong><?php echo e($ord['payment_method']); ?></strong> (<?php echo e($ord['payment_status']); ?>) <?php echo !empty($ord['transaction_id']) ? "• TID: <code>" . e($ord['transaction_id']) . "</code>" : ""; ?> • Shipped to: <?php echo e($ord['address']); ?>, <?php echo e($ord['city']); ?>
                  </div>

                  <!-- Order Tracking Timeline -->
                  <div class="order-tracking-timeline" style="margin-bottom:20px;">
                    <h4 style="font-size:0.9rem; font-weight:600; color:var(--text-heading); margin-bottom:12px;">Order Progress</h4>
                    <div class="tracking-steps">
                      <?php
                      $statuses = ['Order Received', 'Processing', 'Packed', 'Out for Delivery', 'Delivered'];
                      $currentStatus = $ord['order_status'];
                      $statusMap = [
                        'Pending' => 1,
                        'Processing' => 2,
                        'Packed' => 3,
                        'Shipped' => 4,
                        'Delivered' => 5
                      ];
                      $currentStep = $statusMap[$currentStatus] ?? 1;
                      ?>

                      <?php foreach ($statuses as $index => $status): ?>
                        <?php $stepNumber = $index + 1; ?>
                        <div class="tracking-step <?php echo $stepNumber <= $currentStep ? 'completed' : ''; ?> <?php echo $stepNumber === $currentStep ? 'current' : ''; ?>">
                          <div class="tracking-step-icon">
                            <?php if ($stepNumber < $currentStep): ?>
                              <i class="bi bi-check-circle-fill"></i>
                            <?php elseif ($stepNumber === $currentStep): ?>
                              <i class="bi bi-circle-fill"></i>
                            <?php else: ?>
                              <i class="bi bi-circle"></i>
                            <?php endif; ?>
                          </div>
                          <div class="tracking-step-content">
                            <div class="tracking-step-title"><?php echo $status; ?></div>
                            <?php if ($stepNumber === $currentStep): ?>
                              <div class="tracking-step-status">Current Status</div>
                            <?php elseif ($stepNumber < $currentStep): ?>
                              <div class="tracking-step-status">Completed</div>
                            <?php endif; ?>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>

                  <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php foreach ($ord['items'] as $item): ?>
                      <div style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-main); padding:10px 14px; border-radius:var(--radius-sm);">
                        <div style="display:flex; align-items:center; gap:12px;">
                          <img src="assets/images/<?php echo e($item['image']); ?>" alt="<?php echo e($item['name']); ?>" style="width:40px; height:40px; object-fit:contain;" loading="lazy">
                          <div>
                            <strong style="font-size:0.9rem; color:var(--text-heading);"><?php echo e($item['name']); ?></strong>
                            <span style="font-size:0.75rem; color:var(--text-muted); display:block;">Qty: <?php echo $item['qty']; ?> <?php echo $item['size_label'] ? "({$item['size_label']})" : ''; ?></span>
                          </div>
                        </div>
                        <strong style="font-size:0.9rem; color:var(--text-heading);">Rs. <?php echo number_format($item['price'] * $item['qty']); ?></strong>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- TAB 2: PROFILE EDIT -->
      <div class="tab-content <?php echo $activeTab === 'profile' ? 'active' : ''; ?>" id="tab-profile">
        <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:32px; box-shadow:var(--shadow-sm);">
          <h3 style="font-size:1.5rem; margin-bottom:20px; color:var(--text-heading);">Profile Information</h3>

          <form method="POST" action="account.php">
<?php echo csrf_field(); ?>
            <input type="hidden" name="action_type" value="update_profile">

            <div style="margin-bottom:20px;">
              <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Full Name</label>
              <input type="text" name="name" class="input-field" value="<?php echo e($user['name']); ?>" required>
            </div>

            <div style="margin-bottom:20px;">
              <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Email Address</label>
              <input type="email" class="input-field" value="<?php echo e($user['email']); ?>" disabled style="background:var(--bg-main); opacity:0.8;">
              <span style="font-size:0.75rem; color:var(--text-muted);">Email address is permanent and used for order confirmations.</span>
            </div>

            <div style="margin-bottom:20px;">
              <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Contact Phone Number</label>
              <input type="tel" name="phone" class="input-field" value="<?php echo e($user['phone']); ?>" maxlength="11" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" placeholder="03XXXXXXXXX">
            </div>

            <div style="margin-bottom:24px;">
              <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">New Password (Leave blank to keep current)</label>
              <div class="password-toggle-wrap">
                <input type="password" name="new_password" id="account-new-password" class="input-field" placeholder="••••••••">
                <button type="button" class="password-toggle-btn js-toggle-password" data-target="account-new-password" aria-label="Toggle password visibility">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Changes</button>
          </form>
        </div>
      </div>

      <!-- TAB 3: SAVED ADDRESSES -->
      <div class="tab-content <?php echo $activeTab === 'addresses' ? 'active' : ''; ?>" id="tab-addresses">
        <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:32px; box-shadow:var(--shadow-sm);">
          
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
            <div>
              <h3 style="font-size:1.5rem; color:var(--text-heading); margin-bottom:4px;">Saved Delivery Addresses</h3>
              <p style="font-size:0.9rem; color:var(--text-muted); margin:0;">Add and manage your delivery addresses for seamless 1-click checkout.</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="btn-toggle-add-addr" onclick="toggleAddAddressForm()">
              <i class="bi bi-plus-lg me-1"></i> Add New Address
            </button>
          </div>

          <!-- Add Address Form (Toggled by Button) -->
          <div id="add-address-container" style="display:none; background:var(--bg-main); border:1.5px solid var(--border-color); border-radius:var(--radius-md); padding:24px; margin-bottom:28px;">
            <h4 style="font-size:1.15rem; color:var(--text-heading); margin-bottom:16px; display:flex; align-items:center; gap:8px;">
              <i class="bi bi-geo-alt-fill" style="color:var(--primary-purple);"></i> Add New Delivery Address
            </h4>

            <form method="POST" action="account.php" id="add-address-form">
<?php echo csrf_field(); ?>
              <input type="hidden" name="action_type" value="add_address">

              <div style="margin-bottom:16px;">
                <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Street / Full Delivery Address *</label>
                <textarea name="address_line" class="input-field" rows="3" placeholder="House #, Street name, Sector/Block, Landmark..." required style="resize:vertical;"></textarea>
              </div>

              <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:16px;">
                <div>
                  <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">City *</label>
                  <input type="text" name="city" class="input-field" placeholder="e.g. Karachi, Lahore, Islamabad" required>
                </div>
                <div>
                  <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Postal Code</label>
                  <input type="text" name="postal_code" class="input-field" placeholder="e.g. 75500">
                </div>
              </div>

              <div style="margin-bottom:16px;">
                <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Recipient Phone Number *</label>
                <input type="tel" name="phone" class="input-field" value="<?php echo e($user['phone']); ?>" maxlength="11" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" placeholder="03XXXXXXXXX" required>
              </div>

              <div style="margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                <input type="checkbox" name="is_default" id="add_is_default" value="1" <?php echo empty($addresses) ? 'checked' : ''; ?> style="width:18px; height:18px; accent-color:var(--primary-purple); cursor:pointer;">
                <label for="add_is_default" style="font-size:0.9rem; color:var(--text-heading); cursor:pointer; font-weight:500;">
                  Set as default shipping address
                </label>
              </div>

              <div style="display:flex; gap:12px;">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="bi bi-check2-circle me-1"></i> Save Address
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAddAddressForm()">
                  Cancel
                </button>
              </div>
            </form>
          </div>

          <!-- Saved Addresses List -->
          <?php if (empty($addresses)): ?>
            <div style="background:var(--bg-main); padding:36px; text-align:center; border-radius:var(--radius-md); border:1px dashed var(--border-color);">
              <i class="bi bi-geo-alt" style="font-size:2.5rem; color:var(--text-muted); display:block; margin-bottom:10px;"></i>
              <p style="color:var(--text-heading); font-weight:600; margin-bottom:4px;">No Saved Addresses Yet</p>
              <p style="color:var(--text-muted); font-size:0.88rem; margin-bottom:16px;">Save your home or office address now for faster ordering on your next visit.</p>
              <button type="button" class="btn btn-primary btn-sm" onclick="toggleAddAddressForm()">
                <i class="bi bi-plus-lg me-1"></i> Add Your First Address
              </button>
            </div>
          <?php else: ?>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">
              <?php foreach ($addresses as $addr): ?>
                <div style="border:1.5px solid <?php echo $addr['is_default'] ? 'var(--primary-purple)' : 'var(--border-color)'; ?>; background:<?php echo $addr['is_default'] ? 'var(--bg-accent-sage)' : 'var(--bg-surface)'; ?>; border-radius:var(--radius-md); padding:20px; display:flex; flex-direction:column; position:relative; box-shadow:var(--shadow-sm); transition:all 0.2s ease;">
                  
                  <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                    <div>
                      <?php if ($addr['is_default']): ?>
                        <span style="background:var(--primary-purple); color:#fff; font-size:0.75rem; font-weight:700; padding:4px 10px; border-radius:20px; display:inline-flex; align-items:center; gap:4px; text-transform:uppercase;">
                          <i class="bi bi-check-circle-fill"></i> Default Address
                        </span>
                      <?php else: ?>
                        <span style="background:var(--bg-main); color:var(--text-muted); border:1px solid var(--border-color); font-size:0.75rem; font-weight:600; padding:4px 10px; border-radius:20px; display:inline-block;">
                          Secondary Address
                        </span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <p style="font-size:1rem; color:var(--text-heading); font-weight:600; line-height:1.4; margin-bottom:6px;">
                    <i class="bi bi-house-door me-1" style="color:var(--primary-purple);"></i> <?php echo e($addr['address_line']); ?>
                  </p>
                  <p style="font-size:0.9rem; color:var(--text-body); margin-bottom:6px;">
                    <i class="bi bi-pin-map me-1" style="color:var(--accent-terracotta);"></i> <?php echo e($addr['city']); ?><?php echo !empty($addr['postal_code']) ? ', ' . e($addr['postal_code']) : ''; ?>
                  </p>
                  <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:16px;">
                    <i class="bi bi-telephone me-1"></i> Phone: <strong><?php echo e($addr['phone']); ?></strong>
                  </p>

                  <div style="margin-top:auto; padding-top:14px; border-top:1px solid var(--border-color); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                    <div style="display:flex; gap:8px;">
                      <button type="button" class="btn btn-secondary btn-sm" style="padding:6px 12px; font-size:0.78rem;" onclick='openEditAddressModal(<?php echo json_encode($addr); ?>)'>
                        <i class="bi bi-pencil me-1"></i> Edit
                      </button>

                      <form method="POST" action="account.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this address?');">
<?php echo csrf_field(); ?>
                        <input type="hidden" name="action_type" value="delete_address">
                        <input type="hidden" name="address_id" value="<?php echo $addr['id']; ?>">
                        <button type="submit" class="btn btn-sm" style="background:#FEE2E2; color:#DC2626; border:1px solid #FECACA; padding:6px 12px; font-size:0.78rem;">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </div>

                    <?php if (!$addr['is_default']): ?>
                      <form method="POST" action="account.php" style="display:inline;">
<?php echo csrf_field(); ?>
                        <input type="hidden" name="action_type" value="set_default_address">
                        <input type="hidden" name="address_id" value="<?php echo $addr['id']; ?>">
                        <button type="submit" class="btn btn-sm" style="background:transparent; color:var(--primary-purple); border:1px dashed var(--primary-purple); padding:6px 10px; font-size:0.78rem;">
                          Set as Default
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>

                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Address Modal -->
<div id="edit-address-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; padding:20px;">
  <div style="background:var(--bg-surface); border-radius:var(--radius-md); max-width:520px; width:100%; padding:28px; box-shadow:var(--shadow-lg); position:relative;">
    <button type="button" onclick="closeEditAddressModal()" style="position:absolute; top:16px; right:16px; background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">
      <i class="bi bi-x-lg"></i>
    </button>
    
    <h3 style="font-size:1.35rem; color:var(--text-heading); margin-bottom:18px; display:flex; align-items:center; gap:8px;">
      <i class="bi bi-pencil-square" style="color:var(--primary-purple);"></i> Edit Delivery Address
    </h3>

    <form method="POST" action="account.php" id="edit-address-form">
<?php echo csrf_field(); ?>
      <input type="hidden" name="action_type" value="edit_address">
      <input type="hidden" name="address_id" id="edit_address_id" value="">

      <div style="margin-bottom:16px;">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Street / Full Delivery Address *</label>
        <textarea name="address_line" id="edit_address_line" class="input-field" rows="3" required style="resize:vertical;"></textarea>
      </div>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:16px;">
        <div>
          <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">City *</label>
          <input type="text" name="city" id="edit_city" class="input-field" required>
        </div>
        <div>
          <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Postal Code</label>
          <input type="text" name="postal_code" id="edit_postal_code" class="input-field">
        </div>
      </div>

      <div style="margin-bottom:16px;">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Recipient Phone Number *</label>
        <input type="tel" name="phone" id="edit_phone" class="input-field" maxlength="11" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" required>
      </div>

      <div style="margin-bottom:20px; display:flex; align-items:center; gap:8px;">
        <input type="checkbox" name="is_default" id="edit_is_default" value="1" style="width:18px; height:18px; accent-color:var(--primary-purple); cursor:pointer;">
        <label for="edit_is_default" style="font-size:0.9rem; color:var(--text-heading); cursor:pointer; font-weight:500;">
          Set as default shipping address
        </label>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:12px;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeEditAddressModal()">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i> Update Address</button>
      </div>
    </form>
  </div>
</div>

<script>
  function toggleAddAddressForm() {
    const container = document.getElementById('add-address-container');
    const btn = document.getElementById('btn-toggle-add-addr');
    if (!container) return;
    
    if (container.style.display === 'none' || container.style.display === '') {
      container.style.display = 'block';
      if (btn) btn.innerHTML = '<i class="bi bi-dash-lg me-1"></i> Close Form';
    } else {
      container.style.display = 'none';
      if (btn) btn.innerHTML = '<i class="bi bi-plus-lg me-1"></i> Add New Address';
    }
  }

  function openEditAddressModal(addr) {
    const modal = document.getElementById('edit-address-modal');
    if (!modal) return;

    document.getElementById('edit_address_id').value = addr.id;
    document.getElementById('edit_address_line').value = addr.address_line;
    document.getElementById('edit_city').value = addr.city;
    document.getElementById('edit_postal_code').value = addr.postal_code || '';
    document.getElementById('edit_phone').value = addr.phone || '';
    document.getElementById('edit_is_default').checked = (parseInt(addr.is_default) === 1);

    modal.style.display = 'flex';
  }

  function closeEditAddressModal() {
    const modal = document.getElementById('edit-address-modal');
    if (modal) modal.style.display = 'none';
  }

  document.addEventListener('DOMContentLoaded', () => {
    const tabBtns = document.querySelectorAll('.tab-btn[data-tab]');
    const tabContents = document.querySelectorAll('.tab-content[id^="tab-"]');

    tabBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        const target = btn.dataset.tab;
        tabBtns.forEach(b => b.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));

        btn.classList.add('active');
        const targetTab = document.getElementById(`tab-${target}`);
        if (targetTab) targetTab.classList.add('active');
      });
    });

    // Close edit modal on outside click
    const editModal = document.getElementById('edit-address-modal');
    if (editModal) {
      editModal.addEventListener('click', (e) => {
        if (e.target === editModal) closeEditAddressModal();
      });
    }
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
