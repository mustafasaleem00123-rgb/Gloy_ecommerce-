<?php
$adminTitle = 'Customer Messages Inbox';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getDBConnection();
$msg = '';

// Toggle Read Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_read'])) {
    csrf_require();
    $msgId = (int)$_POST['toggle_read'];
    $stmt = $pdo->prepare("SELECT is_read FROM messages WHERE id = ?");
    $stmt->execute([$msgId]);
    $curr = $stmt->fetchColumn();

    if ($curr !== false) {
        $newRead = $curr == 1 ? 0 : 1;
        $upd = $pdo->prepare("UPDATE messages SET is_read = ? WHERE id = ?");
        $upd->execute([$newRead, $msgId]);
        $msg = 'Message status updated.';
    }
}

// Delete Message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    csrf_require();
    $delId = (int)$_POST['delete'];
    $del = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    $del->execute([$delId]);
    $msg = 'Message deleted successfully.';
}

// Fetch Messages
$messages = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC")->fetchAll();
?>

<div class="admin-topbar">
  <div>
    <h1 class="admin-title">Customer Support Messages</h1>
    <p style="color:var(--text-muted); font-size:0.9rem;">Review inquiries sent via the Contact Us form.</p>
  </div>
</div>

<?php if ($msg): ?>
  <div style="background:var(--bg-accent-sage); color:var(--primary-sage); padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px;">
    <?php echo e($msg); ?>
  </div>
<?php endif; ?>

<div class="admin-card">
  <div class="card-header-flex">
    <h3 style="font-size:1.2rem;">Messages Inbox (<?php echo count($messages); ?>)</h3>
  </div>

  <?php if (empty($messages)): ?>
    <p style="color:var(--text-muted); text-align:center; padding:40px 0;">No customer messages in the inbox.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>From</th>
            <th>Subject</th>
            <th>Message</th>
            <th>Date Received</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($messages as $m): ?>
            <tr>
              <td>
                <strong><?php echo e($m['name']); ?></strong><br>
                <small style="color:var(--text-muted);"><?php echo e($m['email']); ?></small>
              </td>
              <td><strong><?php echo e($m['subject']); ?></strong></td>
              <td style="max-width:320px; white-space:normal;"><p style="font-size:0.85rem; margin:0; line-height:1.5;"><?php echo e($m['message']); ?></p></td>
              <td><small><?php echo date('M d, Y H:i', strtotime($m['created_at'])); ?></small></td>
              <td>
                <span class="status-badge <?php echo $m['is_read'] ? 'status-paid' : 'status-pending'; ?>">
                  <?php echo $m['is_read'] ? 'Read' : 'New'; ?>
                </span>
              </td>
              <td>
                <div style="display:inline-flex; gap:6px;">
                  <a href="#" data-post-name="toggle_read" data-post-value="<?php echo (int)$m['id']; ?>" class="btn-table-action btn-enable" style="padding:4px 10px; font-size:0.75rem;">
                    <?php echo $m['is_read'] ? 'Mark Unread' : 'Mark Read'; ?>
                  </a>
                  <a href="#" data-post-name="delete" data-post-value="<?php echo (int)$m['id']; ?>" class="btn-table-action btn-disable" style="padding:4px 10px; font-size:0.75rem;" data-confirm="Delete message?">
                    Delete
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
