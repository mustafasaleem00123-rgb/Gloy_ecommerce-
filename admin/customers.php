<?php
$adminTitle = 'Customer Directory';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getDBConnection();
$msg = '';

// Toggle Customer Status (Active / Disabled)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    csrf_require();
    $userId = (int)$_POST['toggle_status'];
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ? AND role = 'user'");
    $stmt->execute([$userId]);
    $curr = $stmt->fetchColumn();

    if ($curr) {
        $newStatus = ($curr === 'active') ? 'disabled' : 'active';
        $upd = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $upd->execute([$newStatus, $userId]);
        $msg = 'Customer status toggled to ' . $newStatus;
    }
}

// Fetch Customers with Total Orders & Total Spent
$sql = "
    SELECT u.*, 
           COUNT(o.id) as total_orders, 
           COALESCE(SUM(o.total_amount), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.role = 'user'
    GROUP BY u.id
    ORDER BY u.created_at DESC
";
$customers = $pdo->query($sql)->fetchAll();
?>

<div class="admin-topbar">
  <div>
    <h1 class="admin-title">Customer Accounts &amp; Order History</h1>
    <p style="color:var(--text-muted); font-size:0.9rem;">View registered user profiles, lifetime customer value, and manage account access.</p>
  </div>
</div>

<?php if ($msg): ?>
  <div style="background:var(--bg-accent-sage); color:var(--primary-sage); padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px;">
    <?php echo e($msg); ?>
  </div>
<?php endif; ?>

<div class="admin-card">
  <div class="card-header-flex">
    <div>
      <h3 style="font-size:1.25rem; color:var(--text-heading); margin-bottom:4px;">Registered Customers (<?php echo count($customers); ?>)</h3>
      <span style="font-size:0.85rem; color:var(--text-muted);">Manage registered customer accounts, view lifetime spending, and toggle login access.</span>
    </div>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:60px;">ID</th>
          <th>Customer Details</th>
          <th>Phone</th>
          <th>Joined Date</th>
          <th>Orders</th>
          <th>Lifetime Value</th>
          <th>Status</th>
          <th style="text-align:right; width:130px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $c): ?>
          <tr>
            <td><strong style="color:var(--text-muted);">#<?php echo $c['id']; ?></strong></td>
            <td>
              <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:34px; height:34px; border-radius:50%; background:var(--bg-accent-sage); color:var(--primary-purple); font-weight:700; font-size:0.85rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                  <?php echo strtoupper(substr($c['name'], 0, 1)); ?>
                </div>
                <div>
                  <strong style="font-size:0.88rem; color:var(--text-heading); display:block;"><?php echo e($c['name']); ?></strong>
                  <span style="font-size:0.76rem; color:var(--text-muted); display:block;"><?php echo e($c['email']); ?></span>
                </div>
              </div>
            </td>
            <td><span style="white-space:nowrap; font-size:0.82rem;"><?php echo e($c['phone'] ?: '—'); ?></span></td>
            <td><span style="white-space:nowrap; font-size:0.82rem; color:var(--text-muted);"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></span></td>
            <td><strong><?php echo $c['total_orders']; ?></strong> <small style="color:var(--text-muted);">orders</small></td>
            <td><strong style="white-space:nowrap; color:var(--primary-purple); font-family:var(--font-serif); font-size:0.95rem;"><?php echo formatPrice($c['total_spent']); ?></strong></td>
            <td>
              <span class="status-badge <?php echo $c['status'] === 'active' ? 'status-paid' : 'status-cancelled'; ?>">
                <i class="bi bi-<?php echo $c['status'] === 'active' ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
                <?php echo strtoupper($c['status']); ?>
              </span>
            </td>
            <td style="text-align:right; white-space:nowrap;">
              <?php if ($c['status'] === 'active'): ?>
                <a href="#" data-post-name="toggle_status" data-post-value="<?php echo (int)$c['id']; ?>" class="btn-table-action btn-disable" title="Disable Customer Account">
                  <i class="bi bi-slash-circle"></i> Disable
                </a>
              <?php else: ?>
                <a href="#" data-post-name="toggle_status" data-post-value="<?php echo (int)$c['id']; ?>" class="btn-table-action btn-enable" title="Enable Customer Account">
                  <i class="bi bi-check-circle"></i> Enable
                </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
