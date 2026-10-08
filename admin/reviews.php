<?php
$adminTitle = 'Review Moderation';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getDBConnection();
$msg = '';

// Toggle Review Approval Status (1 / 0)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle'])) {
    csrf_require();
    $revId = (int)$_POST['toggle'];
    $stmt = $pdo->prepare("SELECT approved, product_id FROM reviews WHERE id = ?");
    $stmt->execute([$revId]);
    $rev = $stmt->fetch();

    if ($rev) {
        $newApp = $rev['approved'] == 1 ? 0 : 1;
        $upd = $pdo->prepare("UPDATE reviews SET approved = ? WHERE id = ?");
        $upd->execute([$newApp, $revId]);

        // Recalculate average rating & review count for product
        $calcStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as cnt FROM reviews WHERE product_id = ? AND approved = 1");
        $calcStmt->execute([$rev['product_id']]);
        $stats = $calcStmt->fetch();

        $newAvg = round((float)$stats['avg_rating'], 2);
        $newCnt = (int)$stats['cnt'];

        $updProd = $pdo->prepare("UPDATE products SET rating_avg = ?, review_count = ? WHERE id = ?");
        $updProd->execute([$newAvg, $newCnt, $rev['product_id']]);

        $msg = 'Review approval status updated.';
    }
}

// Handle Delete Review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    csrf_require();
    $delId = (int)$_POST['delete'];
    $stmt = $pdo->prepare("SELECT product_id FROM reviews WHERE id = ?");
    $stmt->execute([$delId]);
    $rev = $stmt->fetch();

    if ($rev) {
        $del = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $del->execute([$delId]);

        // Recalculate average rating & review count for product
        $calcStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as cnt FROM reviews WHERE product_id = ? AND approved = 1");
        $calcStmt->execute([$rev['product_id']]);
        $stats = $calcStmt->fetch();

        $newAvg = $stats['cnt'] > 0 ? round((float)$stats['avg_rating'], 2) : 5.00;
        $newCnt = (int)$stats['cnt'];

        $updProd = $pdo->prepare("UPDATE products SET rating_avg = ?, review_count = ? WHERE id = ?");
        $updProd->execute([$newAvg, $newCnt, $rev['product_id']]);

        $msg = 'Review deleted successfully.';
    }
}

// Fetch Reviews
$sql = "
    SELECT r.*, u.name as user_name, u.email as user_email, p.name as product_name, p.image as product_image
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    ORDER BY r.created_at DESC
";
$reviews = $pdo->query($sql)->fetchAll();
?>

<div class="admin-topbar">
  <div>
    <h1 class="admin-title">Product Review Moderation</h1>
    <p style="color:var(--text-muted); font-size:0.9rem;">Approve, hide, or manage customer reviews across all product lines.</p>
  </div>
</div>

<?php if ($msg): ?>
  <div style="background:var(--bg-accent-sage); color:var(--primary-sage); padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px;">
    <?php echo e($msg); ?>
  </div>
<?php endif; ?>

<div class="admin-card">
  <div class="card-header-flex">
    <h3 style="font-size:1.2rem;">All Reviews (<?php echo count($reviews); ?>)</h3>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Product</th>
          <th>Customer</th>
          <th>Rating</th>
          <th>Feedback &amp; Photo</th>
          <th>Date</th>
          <th>Status</th>
          <th style="text-align:right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reviews as $r): ?>
          <tr>
            <td>
              <div style="display:flex; align-items:center; gap:8px;">
                <img src="../assets/images/<?php echo e($r['product_image']); ?>" alt="" style="width:32px; height:32px; object-fit:contain; background:var(--bg-main); border-radius:var(--radius-sm); padding:2px; border:1px solid var(--border-color); flex-shrink:0;">
                <strong style="font-size:0.82rem; color:var(--text-heading);"><?php echo e($r['product_name']); ?></strong>
              </div>
            </td>
            <td>
              <strong style="font-size:0.82rem;"><?php echo e($r['user_name']); ?></strong>
              <div style="color:var(--text-muted); font-size:0.75rem;"><?php echo e($r['user_email']); ?></div>
            </td>
            <td><span style="color:#F59E0B; font-weight:bold; font-size:0.82rem;"><i class="bi bi-star-fill"></i> <?php echo $r['rating']; ?>.0</span></td>
            <td style="max-width:240px; white-space:normal;">
              <p style="font-size:0.82rem; margin:0; line-height:1.4; color:var(--text-body);"><?php echo e($r['comment']); ?></p>
              <?php if (!empty($r['before_image'])): ?>
                <div style="display:flex; gap:6px; align-items:center; margin-top:4px;">
                  <img src="../assets/images/<?php echo e($r['before_image']); ?>" alt="Before/After" style="width:36px; height:36px; object-fit:cover; border-radius:4px; border:1px solid var(--border-color); cursor:pointer;" onclick="window.open('../assets/images/<?php echo e($r['before_image']); ?>', '_blank')">
                  <span style="font-size:0.72rem; color:var(--primary-purple);"><i class="bi bi-image"></i> Photo</span>
                </div>
              <?php endif; ?>
            </td>
            <td><span style="font-size:0.8rem; color:var(--text-muted);"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></span></td>
            <td>
              <span class="status-badge <?php echo $r['approved'] ? 'status-paid' : 'status-pending'; ?>">
                <?php echo $r['approved'] ? 'Approved' : 'Hidden'; ?>
              </span>
            </td>
            <td style="text-align:right;">
              <div style="display:inline-flex; gap:6px;">
                <a href="#" data-post-name="toggle" data-post-value="<?php echo (int)$r['id']; ?>" class="btn-table-action btn-enable" style="padding:4px 10px; font-size:0.75rem;">
                  <i class="bi bi-<?php echo $r['approved'] ? 'eye-slash' : 'eye'; ?>"></i> <?php echo $r['approved'] ? 'Hide' : 'Approve'; ?>
                </a>
                <a href="#" data-post-name="delete" data-post-value="<?php echo (int)$r['id']; ?>" class="btn-table-action btn-disable" style="padding:4px 8px; font-size:0.75rem;" data-confirm="Delete review permanently?" title="Delete Review">
                  <i class="bi bi-trash"></i>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
