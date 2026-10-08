<?php
/**
 * Gloy Skincare - Checkout (Guest Checkout & Multi-Gateway Payment)
 */
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/includes/cart_helper.php';
require_once __DIR__ . '/includes/payment_helper.php';

$cartData = get_cart_items();
$items = $cartData['items'];
$subtotal = $cartData['subtotal'];

if (empty($items)) {
    header('Location: cart.php');
    exit;
}

$currentUser = get_current_user_data();

// Pre-fill user default address if available (for logged-in users)
$pdo = getDBConnection();
$userAddress = null;
if ($currentUser) {
    $addrStmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC LIMIT 1");
    $addrStmt->execute([$_SESSION['user_id']]);
    $userAddress = $addrStmt->fetch();
}

$errors = [];
$name = $currentUser ? $currentUser['name'] : '';
$email = $currentUser ? $currentUser['email'] : '';
$phone = $userAddress ? $userAddress['phone'] : ($currentUser ? $currentUser['phone'] : '');
$address = $userAddress ? $userAddress['address_line'] : '';
$city = $userAddress ? $userAddress['city'] : '';
$postal_code = $userAddress ? $userAddress['postal_code'] : '';
$payment_method = 'COD';
$checkout_type = $currentUser ? 'logged_in' : 'guest';

// Form Submit Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'COD');
    $checkout_type = trim($_POST['checkout_type'] ?? 'guest');
    $newPassword = $_POST['new_password'] ?? '';

    // Comprehensive Server-Side Validation
    if (empty($name)) {
        $errors['name'] = 'Full Name is required.';
    } elseif (strlen($name) < 3 || strlen($name) > 60) {
        $errors['name'] = 'Full Name must be between 3 and 60 characters.';
    } elseif (preg_match('/[0-9]/', $name) || !preg_match("/^[a-zA-Z\s\.\'\-]+$/u", $name)) {
        $errors['name'] = 'Full Name cannot contain numbers. Alphabetic letters only.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
        $errors['email'] = 'A valid email address is required (e.g. name@domain.com).';
    } elseif (strlen($email) > 100) {
        $errors['email'] = 'Email address is too long.';
    } else {
        $emailParts = explode('@', $email);
        if (count($emailParts) !== 2 || !preg_match('/[a-zA-Z]/', $emailParts[0]) || !preg_match('/[a-zA-Z]/', $emailParts[1])) {
            $errors['email'] = 'Email username cannot be purely numbers. It must contain letters (e.g. ayesha@gmail.com or ayesha123@gmail.com).';
        }
    }

    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (empty($cleanPhone) || strlen($cleanPhone) !== 11 || !str_starts_with($cleanPhone, '03')) {
        $errors['phone'] = 'Please enter a valid 11-digit mobile number starting with 03 (e.g. 03168882788).';
    } else {
        $phone = $cleanPhone;
    }

    if (empty($address) || strlen($address) < 5) {
        $errors['address'] = 'Please enter a complete street delivery address (minimum 5 characters).';
    }

    $validCities = ['Karachi', 'Lahore', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Quetta', 'Other'];
    if (empty($city) || !in_array($city, $validCities)) {
        $errors['city'] = 'Please select a valid city from the list.';
    }

    $cleanPostal = preg_replace('/[^0-9]/', '', $postal_code);
    if (empty($cleanPostal) || strlen($cleanPostal) < 4 || strlen($cleanPostal) > 6) {
        $errors['postal_code'] = 'Please enter a valid 5-digit postal code (e.g. 74200).';
    }
    
    $validGateways = ['COD', 'Easypaisa'];
    if (!in_array($payment_method, $validGateways)) {
        $errors['payment_method'] = 'Please select a valid payment method.';
    }

    // Validate account creation password if requested
    $create_account = (!$currentUser && $checkout_type === 'create_account');
    if ($create_account) {
        if (empty($newPassword)) {
            $errors['new_password'] = 'Password is required for account creation.';
        } elseif (strlen($newPassword) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters.';
        }
    }

    // Validate Easypaisa input & proof
    $paymentScreenshotName = null;
    $paymentScreenshotHash = null;
    $transactionIdInput = '';

    if ($payment_method === 'Easypaisa') {
        $epNumber = preg_replace('/[^0-9]/', '', $_POST['easypaisa_number'] ?? '');
        if (empty($epNumber)) {
            $errors['easypaisa_number'] = 'Easypaisa sender mobile number is required.';
        } elseif (strlen($epNumber) !== 11 || !str_starts_with($epNumber, '03')) {
            $errors['easypaisa_number'] = 'Please enter a valid 11-digit mobile number starting with 03 (e.g. 03451234567).';
        }

        // Transaction ID (TID): zaroori, sirf digits, aur pehle kisi order mein use nahi hua hona chahiye
        $rawTrx = trim($_POST['transaction_id'] ?? '');
        if ($rawTrx === '') {
            $errors['transaction_id'] = 'Transaction ID (TID) is required for Easypaisa payments.';
        } elseif (!preg_match('/^[0-9]{8,20}$/', $rawTrx)) {
            $errors['transaction_id'] = 'Transaction ID (TID) must contain numbers only without any letters or spaces (8 to 20 digits).';
        } else {
            $dupTid = $pdo->prepare("SELECT (SELECT COUNT(*) FROM orders WHERE transaction_id = ?) + (SELECT COUNT(*) FROM payments WHERE transaction_id = ?)");
            $dupTid->execute([$rawTrx, $rawTrx]);
            if ((int)$dupTid->fetchColumn() > 0) {
                $errors['transaction_id'] = 'This Transaction ID has already been used for another order. Please enter the TID of your own payment.';
            } else {
                $transactionIdInput = $rawTrx;
            }
        }

        // Payment screenshot: zaroori, asli image, aur pehle kisi order mein use nahi hui hogi
        $hasFile = isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] !== UPLOAD_ERR_NO_FILE;
        if (!$hasFile) {
            $errors['payment_screenshot'] = 'Please upload a screenshot or photo of your Easypaisa payment receipt.';
        } else {
            $file = $_FILES['payment_screenshot'];
            if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
                $errors['payment_screenshot'] = 'Error uploading payment screenshot. Please try again.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $errors['payment_screenshot'] = 'Screenshot file size exceeds 5MB limit.';
            } else {
                // Asli image check: content se (extension ya browser ke bheje MIME par bharosa nahi)
                $imgInfo = @getimagesize($file['tmp_name']);
                $extByType = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];

                if ($imgInfo === false || !isset($extByType[$imgInfo[2]])) {
                    $errors['payment_screenshot'] = 'Invalid image format. Please upload a JPG, PNG, or WEBP receipt.';
                } else {
                    $paymentScreenshotHash = hash_file('sha256', $file['tmp_name']);
                    $dupShot = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE payment_screenshot_hash = ?");
                    $dupShot->execute([$paymentScreenshotHash]);

                    if ((int)$dupShot->fetchColumn() > 0) {
                        $errors['payment_screenshot'] = 'This screenshot has already been submitted with another order. Please upload the receipt of your own payment.';
                    } else {
                        $uploadDir = __DIR__ . '/assets/uploads/payments/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        $newFileName = 'ep_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extByType[$imgInfo[2]];
                        if (move_uploaded_file($file['tmp_name'], $uploadDir . $newFileName)) {
                            @chmod($uploadDir . $newFileName, 0644);
                            $paymentScreenshotName = $newFileName;
                        } else {
                            $errors['payment_screenshot'] = 'Could not save receipt image. Please try again.';
                        }
                    }
                }
            }
        }
    }

    // Agar baqi validation fail hui to upload hui screenshot orphan na rahe
    if (!empty($errors) && $paymentScreenshotName) {
        @unlink(__DIR__ . '/assets/uploads/payments/' . $paymentScreenshotName);
        $paymentScreenshotName = null;
        $paymentScreenshotHash = null;
    }

    if (empty($errors)) {
        try {
            // ATOMIC DATABASE TRANSACTION
            $pdo->beginTransaction();

            $orderNumber = 'GLOY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4))); // unpredictable, 4 billion combinations/day
            $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

            if ($userId !== null) {
                $chkUser = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                $chkUser->execute([$userId]);
                if (!$chkUser->fetch()) {
                    $userId = null;
                }
            }

            // Initial statuses based on payment method
            $initialPaymentStatus = ($payment_method === 'Easypaisa') ? 'Pending Verification' : 'Pending';
            $initialOrderStatus = 'Pending';

            // 1. Insert Order with screenshot and transaction ID
            $insOrder = $pdo->prepare("
                INSERT INTO orders (order_number, user_id, name, email, phone, address, city, postal_code, total_amount, payment_method, payment_status, order_status, payment_screenshot, transaction_id, payment_screenshot_hash)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insOrder->execute([
                $orderNumber,
                $userId,
                $name,
                $email,
                $phone,
                $address,
                $city,
                $postal_code,
                $subtotal,
                $payment_method,
                $initialPaymentStatus,
                $initialOrderStatus,
                $paymentScreenshotName,
                $transactionIdInput ?: null,
                $paymentScreenshotHash
            ]);
            $orderId = $pdo->lastInsertId();

            // 2. Insert initial Payment Record
            $insPayment = $pdo->prepare("
                INSERT INTO payments (order_id, payment_method, transaction_id, amount, status)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insPayment->execute([
                $orderId, 
                $payment_method, 
                $transactionIdInput ?: null, 
                $subtotal, 
                $initialPaymentStatus
            ]);

            // 3. Insert Order Items & Deduct Product Stock
            $insItem = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, variant_id, qty, price)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($items as $item) {
                $variantId = (!empty($item['variant_id']) && (int)$item['variant_id'] > 0) ? (int)$item['variant_id'] : null;
                if ($variantId !== null) {
                    $chkVar = $pdo->prepare("SELECT id FROM product_variants WHERE id = ?");
                    $chkVar->execute([$variantId]);
                    if (!$chkVar->fetch()) {
                        $variantId = null;
                    }
                }

                $insItem->execute([
                    $orderId,
                    $item['product_id'],
                    $variantId,
                    $item['qty'],
                    $item['unit_price']
                ]);

                // Stock: ek hi atomic UPDATE. "stock >= qty" shart database khud check karta hai,
                // is liye do customers ek hi aakhri item nahi khareed sakte.
                $qty = (int)$item['qty'];
                if ($qty < 1) {
                    throw new GloyUserException('Invalid quantity in your cart. Please review your cart and try again.');
                }
                if ($variantId !== null) {
                    $stkStmt = $pdo->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?");
                    $stkStmt->execute([$qty, $variantId, $qty]);
                } else {
                    $stkStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                    $stkStmt->execute([$qty, $item['product_id'], $qty]);
                }
                if ($stkStmt->rowCount() !== 1) {
                    throw new GloyUserException('Sorry, "' . $item['name'] . '" is out of stock or has fewer units available than you requested. Please update your cart.');
                }
            }

            // 4. Process Payment via Gateway Processor
            $paymentProcessor = new PaymentProcessor($pdo);
            $paymentResult = $paymentProcessor->processPayment($orderId, $subtotal, $payment_method, array_merge($_POST, [
                'transaction_id' => $transactionIdInput,
                'payment_screenshot' => $paymentScreenshotName
            ]));

            if (!$paymentResult['success']) {
                throw new GloyUserException($paymentResult['message']);
            }

            // Final status sync
            $finalPaymentStatus = $paymentResult['status'] ?? $initialPaymentStatus;
            $finalOrderStatus = $paymentResult['order_status'] ?? $initialOrderStatus;

            $updateOrderPayment = $pdo->prepare("
                UPDATE orders 
                SET payment_status = ?, order_status = ?, transaction_id = COALESCE(?, transaction_id)
                WHERE id = ?
            ");
            $updateOrderPayment->execute([
                $finalPaymentStatus,
                $finalOrderStatus,
                $paymentResult['transaction_id'] ?? $transactionIdInput,
                $orderId
            ]);

            // 5. Clear Cart
            if ($userId !== null) {
                $clrCart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $clrCart->execute([$userId]);
            } else {
                $clrCart = $pdo->prepare("DELETE FROM cart WHERE session_id = ?");
                $clrCart->execute([session_id()]);
            }

            // Commit Transaction
            $pdo->commit();

            // Auto-save address for existing logged-in user if not already saved
            if ($userId !== null) {
                try {
                    $chkAddr = $pdo->prepare("SELECT id FROM user_addresses WHERE user_id = ? AND address_line = ? AND city = ?");
                    $chkAddr->execute([$userId, $address, $city]);
                    if (!$chkAddr->fetch()) {
                        $countAddr = $pdo->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
                        $countAddr->execute([$userId]);
                        $isDef = ((int)$countAddr->fetchColumn() === 0) ? 1 : 0;
                        $insAddr = $pdo->prepare("
                            INSERT INTO user_addresses (user_id, address_line, city, postal_code, phone, is_default)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $insAddr->execute([$userId, $address, $city, $postal_code, $phone, $isDef]);
                    }
                } catch (Exception $addrEx) {
                    error_log("Address auto-save error during checkout: " . $addrEx->getMessage());
                }
            }

            // Create user account if requested
            if ($create_account && $userId === null) {
                try {
                    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
                    $userStmt = $pdo->prepare("
                        INSERT INTO users (name, email, password_hash, phone, role, status)
                        VALUES (?, ?, ?, ?, 'user', 'active')
                    ");
                    $userStmt->execute([$name, $email, $passwordHash, $phone]);
                    $newUserId = $pdo->lastInsertId();

                    // Save address for user
                    $addrStmt = $pdo->prepare("
                        INSERT INTO user_addresses (user_id, address_line, city, postal_code, phone, is_default)
                        VALUES (?, ?, ?, ?, ?, 1)
                    ");
                    $addrStmt->execute([$newUserId, $address, $city, $postal_code, $phone]);

                    // Link order to new user_id
                    $updOrder = $pdo->prepare("UPDATE orders SET user_id = ? WHERE id = ?");
                    $updOrder->execute([$newUserId, $orderId]);

                    // Seamless login (naya session id)
                    gloy_login_user(['id' => $newUserId, 'name' => $name, 'email' => $email, 'role' => 'user']);
                } catch (Exception $accountEx) {
                    error_log("Account creation error during checkout: " . $accountEx->getMessage());
                }
            }

            // Redirect to Order Confirmation
            // Ownership: sirf yehi browser session is order ka confirmation/activation kar sakta hai
            $_SESSION['last_order_id'] = (int)$orderId;
            header("Location: order_confirmation.php?id=" . $orderId);
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Order fail hua: upload hui screenshot hata dein (warna orphan file reh jati hai)
            if ($paymentScreenshotName) {
                @unlink(__DIR__ . '/assets/uploads/payments/' . $paymentScreenshotName);
            }

            if ($e instanceof GloyUserException) {
                $errors['global'] = $e->getMessage();
            } elseif ($e instanceof PDOException && (string)$e->getCode() === '23000' && $payment_method === 'Easypaisa') {
                // UNIQUE index: do browsers ne ek hi waqt mein wohi TID/screenshot bheji
                gloy_log_error('checkout duplicate payment proof', $e);
                $errors['global'] = 'This payment proof (Transaction ID or screenshot) has already been used for another order.';
            } else {
                gloy_log_error('checkout order failed', $e);
                $errors['global'] = 'We could not process your order right now. Please try again, or contact support at 0316 8882788.';
            }
        }
    }
}

$pageTitle = 'Secure Checkout | Gloy Skincare';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container section">
  <div style="margin-bottom: 28px;">
    <h1 style="font-size: 2.6rem; color:var(--text-heading); margin-bottom:6px;">Secure Checkout</h1>
    <p style="color:var(--text-muted); font-size: 0.95rem;">Complete your order with fast, flexible shipping across Pakistan.</p>
  </div>

  <?php if (isset($errors['global'])): ?>
    <div style="background-color: var(--accent-blush); color: var(--accent-terracotta); padding: 16px 20px; border-radius: var(--radius-md); margin-bottom: 24px; border:1px solid var(--accent-terracotta);">
      <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo e($errors['global']); ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="checkout.php" id="checkout-form" enctype="multipart/form-data" novalidate>
    <?php echo csrf_field(); ?>
    <div class="checkout-grid" style="display:grid; grid-template-columns: 1.85fr 1.15fr; gap:40px; align-items:start;">
      
      <!-- Shipping & Billing & Payment Column -->
      <div style="display:flex; flex-direction:column; gap:28px;">

        <!-- 1. Guest / Account Selection (If Not Logged In) -->
        <?php if (!$currentUser): ?>
          <div style="background:var(--bg-surface); border-radius:var(--radius-md); border:1px solid var(--border-color); padding:24px 28px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
              <h3 style="font-size:1.25rem; color:var(--text-heading); margin:0;">
                <i class="bi bi-person-check text-primary me-2"></i> How would you like to checkout?
              </h3>
              <a href="login.php?redirect=checkout.php" style="font-size:0.85rem; color:var(--primary-purple); font-weight:600;">Already have an account? Log In</a>
            </div>

            <div class="checkout-type-options" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:12px;">
              <label class="checkout-type-card <?php echo $checkout_type === 'guest' ? 'selected' : ''; ?>" style="border:1.5px solid var(--border-color); border-radius:var(--radius-md); padding:16px; cursor:pointer; display:flex; align-items:flex-start; gap:12px; background:var(--bg-main);">
                <input type="radio" name="checkout_type" value="guest" <?php echo $checkout_type === 'guest' ? 'checked' : ''; ?> style="margin-top:4px;">
                <div>
                  <strong style="color:var(--text-heading); display:block; font-size:0.95rem;">Guest Checkout</strong>
                  <span style="font-size:0.8rem; color:var(--text-muted);">No password needed now. Fast 60-second checkout.</span>
                </div>
              </label>

              <label class="checkout-type-card <?php echo $checkout_type === 'create_account' ? 'selected' : ''; ?>" style="border:1.5px solid var(--border-color); border-radius:var(--radius-md); padding:16px; cursor:pointer; display:flex; align-items:flex-start; gap:12px; background:var(--bg-main);">
                <input type="radio" name="checkout_type" value="create_account" <?php echo $checkout_type === 'create_account' ? 'checked' : ''; ?> style="margin-top:4px;">
                <div>
                  <strong style="color:var(--text-heading); display:block; font-size:0.95rem;">Create Account</strong>
                  <span style="font-size:0.8rem; color:var(--text-muted);">Save address &amp; track orders effortlessly.</span>
                </div>
              </label>
            </div>

            <div id="account-creation-panel" style="display:<?php echo $checkout_type === 'create_account' ? 'block' : 'none'; ?>; margin-top:16px; padding-top:16px; border-top:1px dashed var(--border-color);">
              <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Create Password for New Account *</label>
              <div class="password-toggle-wrap">
                <input type="password" name="new_password" id="checkout-password" class="input-field" placeholder="Minimum 8 characters (e.g. Secret@123)">
                <button type="button" class="password-toggle-btn js-toggle-password" data-target="checkout-password" aria-label="Toggle password visibility">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <?php if (isset($errors['new_password'])): ?><span style="color:var(--accent-terracotta); font-size:0.75rem;"><?php echo $errors['new_password']; ?></span><?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- 2. Shipping & Contact Information -->
        <div style="background:var(--bg-surface); border-radius:var(--radius-md); border:1px solid var(--border-color); padding:32px;">
          <h3 style="font-size:1.35rem; margin-bottom:24px; border-bottom:1px solid var(--border-color); padding-bottom:14px; color:var(--text-heading);">
            1. Delivery &amp; Contact Details
          </h3>

          <div class="checkout-form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
            <div>
              <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Full Name *</label>
              <input type="text" name="name" class="input-field" value="<?php echo e($name); ?>" placeholder="e.g. Ayesha Khan" oninput="this.value = this.value.replace(/[0-9]/g, '')" required>
              <?php if (isset($errors['name'])): ?><span style="color:var(--accent-terracotta); font-size:0.75rem;"><?php echo $errors['name']; ?></span><?php endif; ?>
            </div>

            <div>
              <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Email Address (For Order Updates) *</label>
              <input type="email" name="email" class="input-field" value="<?php echo e($email); ?>" placeholder="ayesha@example.com" required>
              <?php if (isset($errors['email'])): ?><span style="color:var(--accent-terracotta); font-size:0.75rem;"><?php echo $errors['email']; ?></span><?php endif; ?>
            </div>
          </div>

          <div class="checkout-form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
            <div>
              <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Phone Number (For Courier Tracking) *</label>
              <input type="tel" name="phone" class="input-field" value="<?php echo e($phone); ?>" maxlength="11" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" placeholder="03168882788" required>
              <?php if (isset($errors['phone'])): ?><span style="color:var(--accent-terracotta); font-size:0.75rem;"><?php echo $errors['phone']; ?></span><?php endif; ?>
            </div>

            <div>
              <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Postal Code *</label>
              <input type="text" name="postal_code" class="input-field" value="<?php echo e($postal_code); ?>" placeholder="74200" required>
              <?php if (isset($errors['postal_code'])): ?><span style="color:var(--accent-terracotta); font-size:0.75rem;"><?php echo $errors['postal_code']; ?></span><?php endif; ?>
            </div>
          </div>

          <div style="margin-bottom:20px;">
            <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Street Delivery Address *</label>
            <input type="text" name="address" class="input-field" value="<?php echo e($address); ?>" placeholder="House/Apartment #, Street, Sector / Area" required>
            <?php if (isset($errors['address'])): ?><span style="color:var(--accent-terracotta); font-size:0.75rem;"><?php echo $errors['address']; ?></span><?php endif; ?>
          </div>

          <div style="margin-bottom:12px;">
            <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">City / Destination *</label>
            <select name="city" class="input-field" id="city-select" required>
              <option value="">Select your city</option>
              <option value="Karachi" <?php echo $city === 'Karachi' ? 'selected' : ''; ?>>Karachi</option>
              <option value="Lahore" <?php echo $city === 'Lahore' ? 'selected' : ''; ?>>Lahore</option>
              <option value="Islamabad" <?php echo $city === 'Islamabad' ? 'selected' : ''; ?>>Islamabad</option>
              <option value="Rawalpindi" <?php echo $city === 'Rawalpindi' ? 'selected' : ''; ?>>Rawalpindi</option>
              <option value="Faisalabad" <?php echo $city === 'Faisalabad' ? 'selected' : ''; ?>>Faisalabad</option>
              <option value="Multan" <?php echo $city === 'Multan' ? 'selected' : ''; ?>>Multan</option>
              <option value="Peshawar" <?php echo $city === 'Peshawar' ? 'selected' : ''; ?>>Peshawar</option>
              <option value="Quetta" <?php echo $city === 'Quetta' ? 'selected' : ''; ?>>Quetta</option>
              <option value="Other" <?php echo $city === 'Other' ? 'selected' : ''; ?>>Other City (Nationwide)</option>
            </select>
            <?php if (isset($errors['city'])): ?><span style="color:var(--accent-terracotta); font-size:0.75rem;"><?php echo $errors['city']; ?></span><?php endif; ?>

            <!-- Delivery Estimator Widget -->
            <div class="delivery-estimator" id="delivery-estimator" style="margin-top:16px; display:none;">
              <div style="background: var(--bg-accent-sage); border: 1px solid var(--primary-purple); border-radius: var(--radius-md); padding: 14px 18px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <i class="bi bi-truck" style="font-size: 1.4rem; color: var(--primary-purple);"></i>
                  <div>
                    <div style="font-weight: 600; color: var(--text-heading); font-size: 0.9rem;">Estimated Delivery Date</div>
                    <div style="color: var(--text-body); font-size: 0.85rem;" id="delivery-date-text">Select a city to view estimated arrival</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Payment Method Selection -->
        <div style="background:var(--bg-surface); border-radius:var(--radius-md); border:1px solid var(--border-color); padding:32px;">
          <h3 style="font-size:1.35rem; margin-bottom:20px; border-bottom:1px solid var(--border-color); padding-bottom:14px; color:var(--text-heading);">
            2. Payment Method
          </h3>

          <div class="payment-methods-grid" style="display:flex; flex-direction:column; gap:16px;">
            
            <!-- Option 1: Cash on Delivery (COD) -->
            <div class="payment-option-box <?php echo $payment_method === 'COD' ? 'active' : ''; ?>" style="border:1.5px solid var(--border-color); border-radius:var(--radius-md); padding:18px; background:var(--bg-main); transition:all 0.2s;">
              <label style="display:flex; align-items:center; justify-content:space-between; cursor:pointer; width:100%;">
                <div style="display:flex; align-items:center; gap:12px;">
                  <input type="radio" name="payment_method" value="COD" <?php echo $payment_method === 'COD' ? 'checked' : ''; ?>>
                  <div>
                    <strong style="color:var(--text-heading); font-size:0.95rem;"><i class="bi bi-cash-stack text-primary me-2"></i> Cash on Delivery (COD)</strong>
                    <p style="font-size:0.8rem; color:var(--text-muted); margin:4px 0 0 0;">Pay with cash at your doorstep when the parcel arrives.</p>
                  </div>
                </div>
                <span class="product-badge" style="position:static; margin:0; font-size:0.75rem;">Default</span>
              </label>
            </div>

            <!-- Option 2: Easypaisa -->
            <div class="payment-option-box <?php echo $payment_method === 'Easypaisa' ? 'active' : ''; ?>" style="border:1.5px solid var(--border-color); border-radius:var(--radius-md); padding:18px; background:var(--bg-main); transition:all 0.2s;">
              <label style="display:flex; align-items:center; justify-content:space-between; cursor:pointer; width:100%;">
                <div style="display:flex; align-items:center; gap:12px;">
                  <input type="radio" name="payment_method" value="Easypaisa" <?php echo $payment_method === 'Easypaisa' ? 'checked' : ''; ?>>
                  <div>
                    <strong style="color:var(--text-heading); font-size:0.95rem;"><i class="bi bi-wallet2 text-primary me-2"></i> Easypaisa Mobile Account</strong>
                    <p style="font-size:0.8rem; color:var(--text-muted); margin:4px 0 0 0;">Direct transfer to official account <strong>03132775608 (Iqra Sohail)</strong>.</p>
                  </div>
                </div>
                <span class="product-badge" style="position:static; margin:0; background:#10B981; color:#fff; font-size:0.75rem;">Easypaisa</span>
              </label>

              <!-- Easypaisa Sub-Form -->
              <div id="easypaisa-details" style="display:<?php echo $payment_method === 'Easypaisa' ? 'block' : 'none'; ?>; margin-top:16px; padding-top:16px; border-top:1px dashed var(--border-color);">
                <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px;">
                  
                  <!-- Merchant Details Banner -->
                  <div style="background:linear-gradient(135deg, rgba(16,185,129,0.1), rgba(16,185,129,0.03)); border:1.5px solid rgba(16,185,129,0.3); border-radius:12px; padding:16px; margin-bottom:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                      <span style="font-size:0.78rem; font-weight:800; color:#059669; text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:6px;">
                        <i class="bi bi-shield-fill-check"></i> Official Easypaisa Account
                      </span>
                      <span style="font-size:0.72rem; background:#10B981; color:#fff; padding:3px 10px; border-radius:12px; font-weight:700;">Verified Merchant</span>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                      <div>
                        <div style="font-size:1.35rem; font-weight:800; color:var(--text-heading); letter-spacing:0.06em; font-family:monospace; line-height:1.2;">
                          03132775608
                        </div>
                        <div style="font-size:0.85rem; color:var(--text-body); margin-top:4px;">
                          Account Title: <strong style="color:#059669; font-size:0.95rem;">Iqra Sohail</strong>
                        </div>
                      </div>

                      <button type="button" onclick="navigator.clipboard.writeText('03132775608'); this.innerHTML='<i class=\'bi bi-check2\'></i> Copied!'; setTimeout(() => this.innerHTML='<i class=\'bi bi-clipboard\'></i> Copy Number', 2000);" style="background:#fff; border:1.5px solid #10B981; color:#059669; font-size:0.82rem; font-weight:700; padding:8px 16px; border-radius:24px; cursor:pointer; transition:all 0.2s; box-shadow:0 2px 6px rgba(16,185,129,0.15);">
                        <i class="bi bi-clipboard me-1"></i> Copy Number
                      </button>
                    </div>

                    <div style="margin-top:12px; padding-top:10px; border-top:1px dashed rgba(16,185,129,0.25); font-size:0.8rem; color:var(--text-muted); display:flex; align-items:center; gap:8px;">
                      <i class="bi bi-info-circle text-primary"></i>
                      <span>Transfer exact order total <strong style="color:var(--text-heading);">Rs. <?php echo number_format($subtotal); ?></strong> via Easypaisa App or USSD (<code>*786#</code>).</span>
                    </div>
                  </div>

                  <!-- 3 Step Inputs Grid -->
                  <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                      <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:6px; color:var(--text-heading);">
                        1. Your Sender Mobile Number *
                      </label>
                      <input type="tel" name="easypaisa_number" class="input-field" placeholder="03451234567" maxlength="11" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" value="<?php echo e($_POST['easypaisa_number'] ?? $phone); ?>" style="font-size:0.88rem; padding:10px 12px;">
                      <?php if (isset($errors['easypaisa_number'])): ?><span style="color:var(--accent-terracotta); font-size:0.75rem; display:block; margin-top:4px;"><?php echo $errors['easypaisa_number']; ?></span><?php endif; ?>
                    </div>

                    <div>
                      <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:6px; color:var(--text-heading);">
                        2. Transaction ID (Digits Only)
                      </label>
                      <input type="text" name="transaction_id" class="input-field" placeholder="e.g. 12345678901" maxlength="20" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" value="<?php echo e($_POST['transaction_id'] ?? ''); ?>" style="font-size:0.88rem; padding:10px 12px;">
                      <?php if (isset($errors['transaction_id'])): ?><span style="color:var(--accent-terracotta); font-size:0.75rem; display:block; margin-top:4px;"><?php echo $errors['transaction_id']; ?></span><?php endif; ?>
                      <small style="color:var(--text-muted); font-size:0.74rem; display:block; margin-top:4px;">Enter numbers only (11-12 digits from SMS/App)</small>
                    </div>
                  </div>

                  <!-- Screenshot Upload Input -->
                  <div>
                    <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:6px; color:var(--text-heading);">
                      3. Attach Payment Screenshot / Receipt (Recommended)
                    </label>
                    <div style="border:2px dashed var(--border-color); border-radius:var(--radius-sm); padding:16px; text-align:center; background:var(--bg-main); transition:all 0.2s; position:relative; cursor:pointer;" id="screenshot-dropzone">
                      <input type="file" name="payment_screenshot" id="payment_screenshot_input" accept="image/png, image/jpeg, image/jpg, image/webp" style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;" onchange="previewPaymentReceipt(this)">
                      <div id="upload-placeholder">
                        <i class="bi bi-cloud-arrow-up" style="font-size:1.8rem; color:#10B981; display:block; margin-bottom:6px;"></i>
                        <span style="font-size:0.85rem; font-weight:600; color:var(--text-heading); display:block;">Click to Upload or Drag &amp; Drop Receipt</span>
                        <span style="font-size:0.75rem; color:var(--text-muted);">JPG, PNG, or WEBP (Max 5MB)</span>
                      </div>
                      <div id="receipt-preview-container" style="display:none; align-items:center; justify-content:center; gap:12px;">
                        <img id="receipt-preview-img" src="" alt="Receipt Preview" style="max-height:80px; border-radius:6px; border:1px solid var(--border-color); object-fit:contain;">
                        <div style="text-align:left;">
                          <strong id="receipt-filename" style="font-size:0.82rem; color:var(--text-heading); display:block;"></strong>
                          <span style="font-size:0.75rem; color:#10B981;"><i class="bi bi-check-circle-fill"></i> Screenshot Attached</span>
                          <span style="font-size:0.72rem; color:var(--text-muted); display:block; text-decoration:underline; cursor:pointer;">Click to replace</span>
                        </div>
                      </div>
                    </div>
                    <?php if (isset($errors['payment_screenshot'])): ?><span style="color:var(--accent-terracotta); font-size:0.75rem; display:block; margin-top:4px;"><?php echo $errors['payment_screenshot']; ?></span><?php endif; ?>
                  </div>

                </div>
              </div>
            </div>

          </div>
        </div>

      </div>

      <!-- Order Summary Column -->
      <div style="background:var(--bg-surface); border-radius:var(--radius-md); border:1px solid var(--border-color); padding:28px; position:sticky; top:100px;">
        <h3 style="font-size:1.35rem; margin-bottom:20px; border-bottom:1px solid var(--border-color); padding-bottom:14px; color:var(--text-heading);">
          Order Summary (<?php echo count($items); ?> items)
        </h3>

        <div style="max-height:280px; overflow-y:auto; margin-bottom:20px; padding-right:4px;">
          <?php foreach ($items as $item): ?>
            <div style="display:flex; gap:14px; margin-bottom:16px; border-bottom:1px dashed var(--border-color); padding-bottom:12px;">
              <img src="assets/images/<?php echo e($item['image']); ?>" alt="<?php echo e($item['name']); ?>" style="width:50px; height:50px; object-fit:contain; background:var(--bg-main); border-radius:var(--radius-sm); padding:4px;" loading="lazy">
              <div style="flex:1;">
                <strong style="font-size:0.88rem; color:var(--text-heading); display:block; line-height:1.3;"><?php echo e($item['name']); ?></strong>
                <span style="font-size:0.75rem; color:var(--text-muted);">Qty: <?php echo $item['qty']; ?> <?php echo $item['size_label'] ? "• {$item['size_label']}" : ''; ?></span>
              </div>
              <strong style="font-size:0.92rem; color:var(--text-heading);">Rs. <?php echo number_format($item['line_total']); ?></strong>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:0.92rem;">
          <span style="color:var(--text-body);">Subtotal</span>
          <span style="color:var(--text-heading); font-weight:600;">Rs. <?php echo number_format($subtotal); ?></span>
        </div>

        <div style="display:flex; justify-content:space-between; margin-bottom:16px; font-size:0.92rem;">
          <span style="color:var(--text-body);">Shipping Across Pakistan</span>
          <span style="color:var(--primary-purple); font-weight:600;">FREE (Promo)</span>
        </div>

        <div style="display:flex; justify-content:space-between; padding-top:16px; border-top:2px dashed var(--border-color); font-size:1.3rem; font-weight:bold; color:var(--text-heading); margin-bottom:24px;">
          <span>Total Payable</span>
          <span style="font-family:var(--font-serif); color:var(--primary-purple);">Rs. <?php echo number_format($subtotal); ?></span>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; padding:14px; font-size:1.05rem;">
          <i class="bi bi-shield-lock me-2"></i> Place Order Now
        </button>

        <div style="display:flex; align-items:center; justify-content:center; gap:8px; margin-top:16px; color:var(--text-muted); font-size:0.78rem;">
          <i class="bi bi-patch-check-fill text-primary"></i> 100% Authentic Skin Formulas &amp; Guaranteed Delivery
        </div>
      </div>

    </div>
  </form>
</div>

<!-- Dynamic Interactivity Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  // 1. Payment Method Switcher
  const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
  const epDetails = document.getElementById('easypaisa-details');
  const optionBoxes = document.querySelectorAll('.payment-option-box');

  paymentRadios.forEach(radio => {
    radio.addEventListener('change', () => {
      optionBoxes.forEach(b => b.classList.remove('active'));
      const parentBox = radio.closest('.payment-option-box');
      if (parentBox) parentBox.classList.add('active');

      if (epDetails) epDetails.style.display = (radio.value === 'Easypaisa') ? 'block' : 'none';
    });
  });

  // 2. Guest / Account Toggle
  const typeRadios = document.querySelectorAll('input[name="checkout_type"]');
  const accPanel = document.getElementById('account-creation-panel');
  const typeCards = document.querySelectorAll('.checkout-type-card');

  typeRadios.forEach(radio => {
    radio.addEventListener('change', () => {
      typeCards.forEach(c => c.classList.remove('selected'));
      radio.closest('.checkout-type-card').classList.add('selected');
      if (accPanel) {
        accPanel.style.display = (radio.value === 'create_account') ? 'block' : 'none';
      }
    });
  });

  // 3. City Delivery Estimator
  const citySelect = document.getElementById('city-select');
  const estimator = document.getElementById('delivery-estimator');
  const dateText = document.getElementById('delivery-date-text');

  const cityTimes = {
    'Karachi': 2,
    'Lahore': 3,
    'Islamabad': 2,
    'Rawalpindi': 2,
    'Faisalabad': 3,
    'Multan': 4,
    'Peshawar': 4,
    'Quetta': 5,
    'Other': 5
  };

  function updateEstimate() {
    const city = citySelect.value;
    if (city && city !== '') {
      estimator.style.display = 'block';
      const days = cityTimes[city] || 4;
      const target = new Date();
      target.setDate(target.getDate() + days);
      const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
      const dayName = dayNames[target.getDay()];
      const dateStr = target.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      dateText.innerHTML = `Order now to receive by <strong>${dayName}, ${dateStr}</strong> in ${city} (${days} business days)`;
    } else {
      estimator.style.display = 'none';
    }
  }

  if (citySelect) {
    citySelect.addEventListener('change', updateEstimate);
    if (citySelect.value) updateEstimate();
  }

  // 5. Client-Side Checkout Form Validation
  const checkoutForm = document.getElementById('checkout-form');
  if (checkoutForm) {
    checkoutForm.addEventListener('submit', (e) => {
      const nameInput = checkoutForm.querySelector('input[name="name"]');
      const emailInput = checkoutForm.querySelector('input[name="email"]');
      const phoneInput = checkoutForm.querySelector('input[name="phone"]');
      const addressInput = checkoutForm.querySelector('input[name="address"]');
      const cityInput = checkoutForm.querySelector('select[name="city"]');
      const paymentInput = checkoutForm.querySelector('input[name="payment_method"]:checked');

      const name = nameInput ? nameInput.value.trim() : '';
      const email = emailInput ? emailInput.value.trim() : '';
      const phone = phoneInput ? phoneInput.value.trim() : '';
      const address = addressInput ? addressInput.value.trim() : '';
      const city = cityInput ? cityInput.value.trim() : '';
      const payment = paymentInput ? paymentInput.value : 'COD';

      if (!name || name.length < 3 || /[0-9]/.test(name) || !/^[a-zA-Z\s\.\'\-]+$/.test(name)) {
        e.preventDefault();
        window.showToast('Please enter a valid full name with letters only (no numbers allowed).', 'error');
        if (nameInput) nameInput.focus();
        return;
      }

      const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
      const emailParts = email.split('@');
      if (!email || !emailPattern.test(email) || emailParts.length !== 2 || !/[a-zA-Z]/.test(emailParts[0]) || !/[a-zA-Z]/.test(emailParts[1])) {
        e.preventDefault();
        window.showToast('Email username cannot be only numbers. Please include letters (e.g. ayesha@gmail.com).', 'error');
        if (emailInput) emailInput.focus();
        return;
      }

      const cleanPhone = phone.replace(/[^0-9]/g, '');
      if (!phone || cleanPhone.length !== 11 || !cleanPhone.startsWith('03')) {
        e.preventDefault();
        window.showToast('Please enter a valid 11-digit mobile number starting with 03 (e.g. 03168882788).', 'error');
        if (phoneInput) phoneInput.focus();
        return;
      }

      if (!address || address.length < 5) {
        e.preventDefault();
        window.showToast('Please enter a complete street address (at least 5 characters).', 'error');
        if (addressInput) addressInput.focus();
        return;
      }

      if (!city) {
        e.preventDefault();
        window.showToast('Please select your delivery city.', 'error');
        if (cityInput) cityInput.focus();
        return;
      }

      if (payment === 'Easypaisa') {
        const tidInput = checkoutForm.querySelector('input[name="transaction_id"]');
        const fileInput = checkoutForm.querySelector('input[name="payment_screenshot"]');
        const tid = tidInput ? tidInput.value.trim() : '';
        const cleanTid = tid.replace(/[^0-9]/g, '');

        if (!cleanTid || cleanTid.length < 6) {
          e.preventDefault();
          window.showToast('Please enter a valid Easypaisa Transaction ID (digits only, 6-25 digits).', 'error');
          if (tidInput) tidInput.focus();
          return;
        }

        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
          e.preventDefault();
          window.showToast('Please upload a screenshot or photo of your Easypaisa payment receipt.', 'error');
          if (fileInput) fileInput.focus();
          return;
        }
      }
    });
  }
});

// 4. Live Payment Receipt Preview
function previewPaymentReceipt(input) {
  const previewContainer = document.getElementById('receipt-preview-container');
  const uploadPlaceholder = document.getElementById('upload-placeholder');
  const previewImg = document.getElementById('receipt-preview-img');
  const filenameLabel = document.getElementById('receipt-filename');

  if (input.files && input.files[0]) {
    const file = input.files[0];
    const reader = new FileReader();

    reader.onload = function(e) {
      if (previewImg) previewImg.src = e.target.result;
      if (filenameLabel) filenameLabel.textContent = file.name;
      if (uploadPlaceholder) uploadPlaceholder.style.display = 'none';
      if (previewContainer) previewContainer.style.display = 'flex';
    };

    reader.readAsDataURL(file);
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
