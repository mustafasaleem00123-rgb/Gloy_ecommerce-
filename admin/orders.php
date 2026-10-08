<?php
$adminTitle = 'Order Fulfillment Management';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getDBConnection();
$msg = '';
$errorMsg = '';

// Handle Status or Payment Approvals
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_require();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'];

    if ($action === 'approve_payment' && $orderId > 0) {
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'Paid', order_status = 'Processing' WHERE id = ?");
        $stmt->execute([$orderId]);

        // Also update payments table
        $stmtPay = $pdo->prepare("UPDATE payments SET status = 'Paid' WHERE order_id = ?");
        $stmtPay->execute([$orderId]);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'payment_status' => 'Paid', 'order_status' => 'Processing']);
            exit;
        }
        $msg = "Order #{$orderId} payment approved successfully! Order status updated to Processing.";
    } elseif ($action === 'update_status' && $orderId > 0) {
        $newStatus = trim($_POST['order_status'] ?? '');
        $allowedStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
        if (in_array($newStatus, $allowedStatuses)) {
            $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $orderId]);
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            }
            $msg = 'Order fulfillment status updated successfully.';
        }
    } elseif ($action === 'update_payment_status' && $orderId > 0) {
        $newPayStatus = trim($_POST['payment_status'] ?? '');
        $allowedPayStatuses = ['Pending', 'Pending Verification', 'Paid', 'Failed', 'Refunded'];
        if (in_array($newPayStatus, $allowedPayStatuses)) {
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
            $stmt->execute([$newPayStatus, $orderId]);

            $stmtPay = $pdo->prepare("UPDATE payments SET status = ? WHERE order_id = ?");
            $stmtPay->execute([$newPayStatus, $orderId]);

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            }
            $msg = 'Payment status updated successfully.';
        }
    }
}

// Fetch Orders
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$payFilter = isset($_GET['payment']) ? trim($_GET['payment']) : '';

$sql = "SELECT * FROM orders WHERE 1=1";
if (!empty($statusFilter)) {
    $sql .= " AND order_status = " . $pdo->quote($statusFilter);
}
if (!empty($payFilter)) {
    $sql .= " AND payment_status = " . $pdo->quote($payFilter);
}
$sql .= " ORDER BY created_at DESC";

$orders = $pdo->query($sql)->fetchAll();

// Count pending verifications for quick counter badge
$pendingVerifCount = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'Pending Verification'")->fetchColumn();

// Duplicate Easypaisa proof detection: wohi TID ya wohi screenshot (file hash) doosre order mein bhi?
$dupTidIds = [];
$dupShotIds = [];
foreach ($pdo->query("SELECT transaction_id FROM orders WHERE transaction_id IS NOT NULL AND transaction_id != '' GROUP BY transaction_id HAVING COUNT(*) > 1")->fetchAll(PDO::FETCH_COLUMN) as $tid) {
    $dupTidIds[$tid] = true;
}
foreach ($pdo->query("SELECT payment_screenshot_hash FROM orders WHERE payment_screenshot_hash IS NOT NULL AND payment_screenshot_hash != '' GROUP BY payment_screenshot_hash HAVING COUNT(*) > 1")->fetchAll(PDO::FETCH_COLUMN) as $h) {
    $dupShotIds[$h] = true;
}

// Fetch items for each order
foreach ($orders as &$ord) {
    $ord['dup_tid']  = !empty($ord['transaction_id']) && isset($dupTidIds[$ord['transaction_id']]);
    $ord['dup_shot'] = !empty($ord['payment_screenshot_hash']) && isset($dupShotIds[$ord['payment_screenshot_hash']]);
    $ord['dup_warning'] = ($ord['dup_tid'] || $ord['dup_shot']);

    $itemStmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image, pv.size_label
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_variants pv ON oi.variant_id = pv.id
        WHERE oi.order_id = ?
    ");
    $itemStmt->execute([$ord['id']]);
    $ord['items'] = $itemStmt->fetchAll();
}
?>

<div class="admin-topbar">
  <div>
    <h1 class="admin-title">Customer Orders &amp; Fulfillment</h1>
    <p style="color:var(--text-muted); font-size:0.9rem;">Review incoming orders, verify Easypaisa transfer screenshots, approve payments, and dispatch packages.</p>
  </div>

  <?php if ($pendingVerifCount > 0): ?>
    <a href="orders.php?payment=Pending+Verification" style="background:#FEF3C7; border:1px solid #F59E0B; color:#92400E; padding:8px 16px; border-radius:20px; font-size:0.85rem; font-weight:700; display:flex; align-items:center; gap:8px; text-decoration:none;">
      <i class="bi bi-exclamation-circle-fill text-warning"></i>
      <span><?php echo $pendingVerifCount; ?> Easypaisa Payment(s) Need Verification</span>
    </a>
  <?php endif; ?>
</div>

<?php if ($msg): ?>
  <div style="background:var(--bg-accent-sage); color:var(--primary-sage); padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; display:flex; align-items:center; gap:10px;">
    <i class="bi bi-check-circle-fill"></i>
    <span><?php echo e($msg); ?></span>
  </div>
<?php endif; ?>

<div class="admin-card">
  <div class="card-header-flex" style="flex-wrap:wrap; gap:12px;">
    <h3 style="font-size:1.2rem;">All Orders (<?php echo count($orders); ?>)</h3>
    
    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
      <div style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:600;">Fulfillment:</label>
        <select onchange="applyFilters();" id="statusFilterSelect" class="input-field" style="width:auto; padding:6px 14px; font-size:0.85rem;">
          <option value="" <?php echo empty($statusFilter) ? 'selected' : ''; ?>>All Statuses</option>
          <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
          <option value="Processing" <?php echo $statusFilter === 'Processing' ? 'selected' : ''; ?>>Processing</option>
          <option value="Shipped" <?php echo $statusFilter === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
          <option value="Delivered" <?php echo $statusFilter === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
          <option value="Cancelled" <?php echo $statusFilter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
        </select>
      </div>

      <div style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:600;">Payment:</label>
        <select onchange="applyFilters();" id="paymentFilterSelect" class="input-field" style="width:auto; padding:6px 14px; font-size:0.85rem;">
          <option value="" <?php echo empty($payFilter) ? 'selected' : ''; ?>>All Payments</option>
          <option value="Pending Verification" <?php echo $payFilter === 'Pending Verification' ? 'selected' : ''; ?>>Pending Verification</option>
          <option value="Paid" <?php echo $payFilter === 'Paid' ? 'selected' : ''; ?>>Paid</option>
          <option value="Pending" <?php echo $payFilter === 'Pending' ? 'selected' : ''; ?>>Pending (COD)</option>
        </select>
      </div>
    </div>
  </div>

  <div class="table-responsive" style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="data-table" style="width: 100%; border-collapse: collapse; table-layout: auto;">
      <thead>
        <tr style="background: var(--bg-main);">
          <th style="padding: 10px 8px; white-space: nowrap;">Order Ref</th>
          <th style="padding: 10px 8px;">Customer</th>
          <th style="padding: 10px 8px;">City / Address</th>
          <th style="padding: 10px 8px; white-space: nowrap;">Total</th>
          <th style="padding: 10px 8px;">Payment &amp; Proof</th>
          <th style="padding: 10px 8px; white-space: nowrap;">Fulfillment</th>
          <th style="padding: 10px 8px; text-align: right; white-space: nowrap;">Details</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:36px; color:var(--text-muted);">
              <i class="bi bi-inbox" style="font-size: 2rem; display:block; margin-bottom: 8px;"></i>
              No orders found matching the selected filter.
            </td>
          </tr>
        <?php endif; ?>

        <?php foreach ($orders as $ord): ?>
          <tr>
            <td style="padding: 10px 8px; white-space: nowrap;">
              <strong style="color:var(--text-heading); font-size:0.88rem; font-family: monospace; letter-spacing: 0.02em; display:block;"><?php echo e($ord['order_number']); ?></strong>
              <small style="color:var(--text-muted); font-size:0.72rem;"><?php echo date('M d, Y h:i A', strtotime($ord['created_at'])); ?></small>
            </td>
            <td style="padding: 10px 8px; max-width: 150px;">
              <strong style="color:var(--text-heading); font-size:0.86rem; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo e($ord['name']); ?>"><?php echo e($ord['name']); ?></strong>
              <small style="color:var(--text-muted); font-size:0.75rem; font-family:monospace; display:block;"><?php echo e($ord['phone']); ?></small>
            </td>
            <td style="padding: 10px 8px; max-width: 140px; font-size:0.8rem; line-height: 1.3;">
              <strong style="color:var(--text-heading); display:block;"><?php echo e($ord['city']); ?> <span style="font-size:0.72rem; color:var(--text-muted); font-weight:normal;">(<?php echo e($ord['postal_code']); ?>)</span></strong>
              <small style="color:var(--text-muted); display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo e($ord['address']); ?>"><?php echo e($ord['address']); ?></small>
            </td>
            <td style="padding: 10px 8px; white-space: nowrap;">
              <strong style="color:var(--primary-purple); font-family:var(--font-serif); font-size:0.98rem;">
                <?php echo formatPrice($ord['total_amount']); ?>
              </strong>
            </td>
            <td style="padding: 10px 8px;">
              <div style="display:flex; flex-direction:column; gap:4px; align-items:flex-start;">
                <div>
                  <span class="status-badge <?php echo in_array($ord['payment_status'], ['Paid', 'Completed']) ? 'status-paid' : ($ord['payment_status'] === 'Pending Verification' ? 'status-pending' : 'status-pending'); ?>" style="<?php echo $ord['payment_status'] === 'Pending Verification' ? 'background:#FEF3C7; color:#92400E; border:1px solid #FCD34D; font-weight:700; font-size:0.68rem; padding:2px 8px;' : 'font-size:0.68rem; padding:2px 8px;'; ?>">
                    <?php echo e($ord['payment_method']); ?>: <?php echo e($ord['payment_status']); ?>
                  </span>
                </div>

                <?php if (!empty($ord['transaction_id'])): ?>
                  <div style="font-family:monospace; font-weight:700; color:var(--text-heading); font-size:0.75rem; background:var(--bg-main); padding:2px 6px; border-radius:4px; border:1px solid var(--border-color); max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="TID: <?php echo e($ord['transaction_id']); ?>">
                    TID: <?php echo e($ord['transaction_id']); ?>
                  </div>
                <?php endif; ?>

                <?php if (!empty($ord['dup_warning'])): ?>
                  <div style="display:inline-flex; align-items:center; gap:4px; background:#FEE2E2; color:#991B1B; border:1px solid #FCA5A5; font-size:0.68rem; font-weight:700; padding:2px 8px; border-radius:20px; white-space:nowrap;" title="Is order ka <?php echo $ord['dup_tid'] && $ord['dup_shot'] ? 'TID aur screenshot' : ($ord['dup_tid'] ? 'TID' : 'screenshot'); ?> kisi aur order mein bhi use hua hai. Approve karne se pehle check karein.">
                    <i class="bi bi-exclamation-triangle-fill"></i> Duplicate <?php echo $ord['dup_tid'] && $ord['dup_shot'] ? 'TID + Screenshot' : ($ord['dup_tid'] ? 'TID' : 'Screenshot'); ?>
                  </div>
                <?php endif; ?>

                <div style="display:flex; gap:4px; flex-wrap:wrap; align-items:center; margin-top:2px;">
                  <?php if (!empty($ord['payment_screenshot'])): ?>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding:3px 8px; font-size:0.72rem; border-color:#10B981; color:#059669; background:#ECFDF5; white-space:nowrap;" onclick="viewReceiptModal('<?php echo e($ord['order_number']); ?>', '<?php echo e($ord['name']); ?>', '<?php echo e($ord['transaction_id'] ?? 'N/A'); ?>', '../receipt.php?id=<?php echo (int)$ord['id']; ?>', <?php echo $ord['id']; ?>, '<?php echo e($ord['payment_status']); ?>', <?php echo $ord['dup_warning'] ? 'true' : 'false'; ?>)">
                      <i class="bi bi-file-earmark-image-fill me-1"></i> Receipt
                    </button>
                  <?php endif; ?>

                  <?php if ($ord['payment_status'] === 'Pending Verification'): ?>
                    <form method="POST" action="orders.php" style="margin:0;"<?php if (!empty($ord['dup_warning'])): ?> onsubmit="return confirm('WARNING: Is order ka TID ya screenshot kisi aur order mein bhi use hua hai. Kya aap waqai payment approve karna chahte hain?');"<?php endif; ?>>
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="approve_payment">
                      <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                      <button type="submit" class="btn btn-primary btn-sm" style="padding:3px 8px; font-size:0.72rem; background:#10B981; border-color:#10B981; white-space:nowrap;">
                        <i class="bi bi-check2-circle me-1"></i> Approve
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td style="padding: 10px 8px; white-space: nowrap;">
              <select class="input-field js-order-status-change" data-order-id="<?php echo $ord['id']; ?>" style="padding:4px 6px; font-size:0.78rem; width:105px; font-weight:600;">
                <?php foreach (['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] as $st): ?>
                  <option value="<?php echo $st; ?>" <?php echo $ord['order_status'] === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td style="padding: 10px 8px; text-align: right; white-space: nowrap;">
              <button type="button" class="btn btn-secondary btn-sm" onclick="toggleItems(<?php echo $ord['id']; ?>)" style="padding: 4px 8px; font-size: 0.75rem; white-space: nowrap;">
                <i class="bi bi-box-seam me-1"></i> Items (<?php echo count($ord['items']); ?>)
              </button>
            </td>
          </tr>

          <!-- Expanded Order Items Row -->
          <tr id="items-row-<?php echo $ord['id']; ?>" style="display:none; background:var(--bg-main);">
            <td colspan="7" style="padding:16px 24px;">
              <strong style="display:block; margin-bottom:10px; color:var(--text-heading);">Purchased Products:</strong>
              <div style="display:flex; flex-wrap:wrap; gap:16px;">
                <?php foreach ($ord['items'] as $it): ?>
                  <div style="background:var(--bg-surface); padding:8px 14px; border-radius:var(--radius-sm); border:1px solid var(--border-color); display:flex; align-items:center; gap:10px;">
                    <img src="../assets/images/<?php echo e($it['image']); ?>" alt="" style="width:36px; height:36px; object-fit:contain;">
                    <span style="font-size:0.85rem; font-weight:600; color:var(--text-heading);"><?php echo e($it['name']); ?></span>
                    <span style="font-size:0.8rem; color:var(--text-muted);"><?php echo $it['size_label'] ? "({$it['size_label']})" : ''; ?> x <?php echo $it['qty']; ?></span>
                    <strong style="font-size:0.85rem; color:var(--primary-purple);"><?php echo formatPrice($it['price'] * $it['qty']); ?></strong>
                  </div>
                <?php endforeach; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Screenshot / Receipt Viewer Modal -->
<div id="receiptModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); z-index:9999; align-items:center; justify-content:center; padding:20px;">
  <div style="background:var(--bg-surface); border-radius:var(--radius-lg); max-width:580px; width:100%; max-height:90vh; display:flex; flex-direction:column; border:1px solid var(--border-color); box-shadow:var(--shadow-xl); overflow:hidden;">
    
    <div style="padding:18px 24px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; background:var(--bg-main);">
      <div>
        <h4 style="margin:0; font-size:1.15rem; color:var(--text-heading);" id="modalOrderRef">Payment Receipt Proof</h4>
        <small style="color:var(--text-muted);" id="modalCustomerInfo"></small>
      </div>
      <button type="button" onclick="closeReceiptModal()" style="background:transparent; border:none; font-size:1.4rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>

    <div style="padding:20px; overflow-y:auto; text-align:center; background:#1e1e24;">
      <img id="modalReceiptImg" src="" alt="Payment Screenshot" style="max-width:100%; max-height:480px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.3); object-fit:contain;">
    </div>

    <div style="padding:16px 24px; border-top:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; background:var(--bg-surface);">
      <div>
        <span style="font-size:0.85rem; color:var(--text-body);">Transaction ID: <strong id="modalTrxId" style="color:var(--text-heading); font-family:monospace;"></strong></span>
      </div>

      <div style="display:flex; gap:10px;">
        <a id="modalDownloadLink" href="" target="_blank" class="btn btn-secondary btn-sm">
          <i class="bi bi-box-arrow-up-right me-1"></i> Open Full Image
        </a>
        <form id="modalApproveForm" method="POST" action="orders.php" style="display:inline;" onsubmit="return !window._receiptIsDuplicate || confirm('WARNING: Is order ka TID ya screenshot kisi aur order mein bhi use hua hai. Kya aap waqai payment approve karna chahte hain?');">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="approve_payment">
          <input type="hidden" name="order_id" id="modalOrderId" value="">
          <button type="submit" id="modalApproveBtn" class="btn btn-primary btn-sm" style="background:#10B981; border-color:#10B981;">
            <i class="bi bi-check2-circle me-1"></i> Approve &amp; Dispatch
          </button>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
function toggleItems(id) {
  const row = document.getElementById('items-row-' + id);
  if (row) {
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
  }
}

function applyFilters() {
  const status = document.getElementById('statusFilterSelect').value;
  const payment = document.getElementById('paymentFilterSelect').value;
  let query = [];
  if (status) query.push('status=' + encodeURIComponent(status));
  if (payment) query.push('payment=' + encodeURIComponent(payment));
  location = 'orders.php' + (query.length > 0 ? '?' + query.join('&') : '');
}

function viewReceiptModal(orderRef, customerName, trxId, imgUrl, orderId, paymentStatus, isDuplicate) {
  window._receiptIsDuplicate = !!isDuplicate;
  document.getElementById('modalOrderRef').textContent = 'Receipt: ' + orderRef;
  document.getElementById('modalCustomerInfo').textContent = 'Customer: ' + customerName;
  document.getElementById('modalTrxId').textContent = trxId;
  document.getElementById('modalReceiptImg').src = imgUrl;
  document.getElementById('modalDownloadLink').href = imgUrl;
  document.getElementById('modalOrderId').value = orderId;
  
  const approveBtn = document.getElementById('modalApproveBtn');
  if (approveBtn) {
    approveBtn.style.display = (paymentStatus === 'Paid') ? 'none' : 'inline-block';
  }

  const modal = document.getElementById('receiptModal');
  modal.style.display = 'flex';
}

function closeReceiptModal() {
  document.getElementById('receiptModal').style.display = 'none';
}

window.addEventListener('click', (e) => {
  const modal = document.getElementById('receiptModal');
  if (e.target === modal) {
    closeReceiptModal();
  }
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

