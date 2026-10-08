<?php
/**
 * Gloy Skincare - Reset Password Handler
 */
require_once __DIR__ . '/includes/auth_middleware.php';

$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$error = '';
$success = '';
$validToken = false;
$userEmail = '';

$pdo = getDBConnection();

if (!empty($token)) {
    $tokenHash = hash('sha256', $token);
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_expires > ?");
    $stmt->execute([$tokenHash, $now]);
    $user = $stmt->fetch();

    if ($user) {
        $validToken = true;
        $userEmail = $user['email'];
    } else {
        $error = 'This password reset link is invalid or has expired. Please request a new link.';
    }
} else {
    $error = 'No reset token provided. Please check the link or request a new reset link.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    csrf_require();
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        $upd = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $upd->execute([$newHash, $user['id']]);

        // Clear rate limit lock on login
        gloy_rl_clear('login', $userEmail);

        $success = 'Your password has been reset successfully! You can now log in with your new password.';
        $validToken = false;
    }
}

$pageTitle = 'Reset Password';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container section">
  <div style="max-width:450px; margin:0 auto; background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:40px; box-shadow:var(--shadow-md);">
    <div style="text-align:center; margin-bottom:28px;">
      <h1 style="font-size:2rem; margin-bottom:8px;">Set New Password</h1>
      <?php if ($userEmail): ?>
        <p style="color:var(--text-muted); font-size:0.9rem;">Resetting password for <strong><?php echo e($userEmail); ?></strong></p>
      <?php endif; ?>
    </div>

    <?php if ($success): ?>
      <div style="background-color:var(--bg-accent-sage); color:var(--primary-sage); padding:16px; border-radius:var(--radius-md); font-size:0.9rem; margin-bottom:24px; text-align:center;">
        <i class="bi bi-check-circle-fill me-1" style="font-size:1.2rem;"></i><br>
        <?php echo e($success); ?>
        <div style="margin-top:16px;">
          <a href="login.php" class="btn btn-primary btn-sm">Log In Now</a>
        </div>
      </div>
    <?php elseif ($error && !$validToken): ?>
      <div style="background-color:var(--accent-blush); color:var(--accent-terracotta); padding:14px; border-radius:var(--radius-md); font-size:0.9rem; margin-bottom:20px; text-align:center;">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo e($error); ?>
        <div style="margin-top:16px;">
          <a href="forgot_password.php" class="btn btn-secondary btn-sm">Request New Link</a>
        </div>
      </div>
    <?php else: ?>

      <?php if ($error): ?>
        <div style="background-color:var(--accent-blush); color:var(--accent-terracotta); padding:12px; border-radius:var(--radius-md); font-size:0.85rem; margin-bottom:20px;">
          <?php echo e($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="reset_password.php?token=<?php echo urlencode($token); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="token" value="<?php echo e($token); ?>">

        <div style="margin-bottom:18px;">
          <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">New Password * (Min 6 chars)</label>
          <div class="password-toggle-wrap">
            <input type="password" name="password" id="reset-new-password" class="input-field" required placeholder="••••••••">
            <button type="button" class="password-toggle-btn js-toggle-password" data-target="reset-new-password" aria-label="Toggle password visibility">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <div style="margin-bottom:24px;">
          <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">Confirm New Password *</label>
          <div class="password-toggle-wrap">
            <input type="password" name="confirm_password" id="reset-confirm-password" class="input-field" required placeholder="••••••••">
            <button type="button" class="password-toggle-btn js-toggle-password" data-target="reset-confirm-password" aria-label="Toggle password visibility">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; margin-bottom:20px;">Update Password</button>
      </form>

    <?php endif; ?>

    <div style="text-align:center; font-size:0.85rem;">
      <a href="login.php" style="color:var(--text-muted);">← Back to Login</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
