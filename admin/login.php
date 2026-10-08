<?php
require_once __DIR__ . '/../includes/security.php'; // session + security bootstrap
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

if (is_admin()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter admin credentials.';
    } elseif (($wait = gloy_rl_locked('admin_login', $email)) > 0) {
        // Brute-force lock: 5 galat tries => 15 minute
        $error = gloy_rl_message($wait);
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && ($admin['status'] ?? 'active') !== 'disabled' && password_verify($password, $admin['password_hash'])) {
            gloy_rl_clear('admin_login', $email);
            gloy_login_user($admin);

            header('Location: dashboard.php');
            exit;
        } else {
            gloy_rl_fail('admin_login', $email);
            $error = 'Invalid admin credentials or non-admin account.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login | Gloy Skincare</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/components.css">
</head>
<body style="background:var(--bg-main); display:flex; align-items:center; justify-content:center; min-height:100vh;">

  <div style="width:100%; max-width:420px; background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:40px; box-shadow:var(--shadow-md);">
    <div style="text-align:center; margin-bottom:28px;">
      <a href="../index.php" class="brand-logo" style="display:inline-block; margin-bottom:8px;">GLOY<span>.</span></a>
      <h2 style="font-size:1.5rem; margin-bottom:4px;">Admin Portal Log In</h2>
      <p style="font-size:0.85rem; color:var(--text-muted);">Enter administrative credentials to access management tools.</p>
    </div>

    <?php if ($error): ?>
      <div style="background-color:var(--accent-blush); color:var(--accent-terracotta); padding:12px 16px; border-radius:var(--radius-md); font-size:0.85rem; margin-bottom:20px;">
        <?php echo e($error); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <?php echo csrf_field(); ?>
      <div style="margin-bottom:18px;">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">Admin Email</label>
        <input type="email" name="email" class="input-field" value="<?php echo e($_POST['email'] ?? ''); ?>" placeholder="gloyskincare@gmail.com" required>
      </div>

      <div style="margin-bottom:24px;">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">Password</label>
        <input type="password" name="password" class="input-field" required placeholder="••••••••">
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;">Log In to Portal</button>
    </form>

    <div style="text-align:center; margin-top:20px; font-size:0.85rem;">
      <a href="../index.php" style="color:var(--text-muted);">← Return to Public Storefront</a>
    </div>
  </div>

</body>
</html>
