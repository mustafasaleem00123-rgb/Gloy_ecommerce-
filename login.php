<?php
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/includes/cart_helper.php';

if (is_logged_in()) {
    header('Location: ' . (is_admin() ? 'admin/dashboard.php' : 'index.php'));
    exit;
}

$error = '';
$notRegistered = false;
$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } elseif (($wait = gloy_rl_locked('login', $email)) > 0) {
        // Brute-force lock: 5 galat tries => 15 minute
        $error = gloy_rl_message($wait);
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // User does not exist in the database
            gloy_rl_fail('login', $email);
            $notRegistered = true;
        } elseif (!password_verify($password, $user['password_hash'])) {
            // Incorrect password
            gloy_rl_fail('login', $email);
            $error = 'Incorrect password. Please verify your credentials or click "Forgot password?" below.';
        } elseif ($user['status'] === 'disabled') {
            $error = 'Your account has been suspended. Please contact customer support at 0316 8882788.';
        } else {
            gloy_rl_clear('login', $email);

            // Guest cart pehle merge karein, phir session id badlein (session fixation se bachao)
            merge_guest_cart_to_user($user['id']);
            gloy_login_user($user);

            // Redirect based on role or saved redirect URL
            if (isset($_SESSION['redirect_after_login'])) {
                $url = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: $url");
            } elseif ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit;
        }
    }
}

$pageTitle = 'Log In';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container section">
  <div style="max-width:460px; margin:0 auto; background:var(--bg-surface); border:1.5px solid var(--border-color); border-radius:var(--radius-lg); padding:40px; box-shadow:var(--shadow-md);">
    <div style="text-align:center; margin-bottom:28px;">
      <h1 style="font-size:2.2rem; margin-bottom:8px; color:var(--text-heading);">Welcome Back</h1>
      <p style="color:var(--text-muted); font-size:0.95rem;">Log in to access your skincare routine &amp; order history.</p>
    </div>

    <?php if ($notRegistered): ?>
      <div style="background-color:#FFFBEB; color:#92400E; border:1.5px solid #FCD34D; padding:16px 18px; border-radius:var(--radius-md); font-size:0.92rem; margin-bottom:24px; display:flex; align-items:flex-start; gap:12px; box-shadow:var(--shadow-sm);">
        <i class="bi bi-person-exclamation" style="font-size:1.6rem; color:#D97706; flex-shrink:0; line-height:1;"></i>
        <div>
          <strong style="display:block; margin-bottom:4px; font-size:0.98rem; color:#78350F;">Account Not Found!</strong>
          <span style="display:block; line-height:1.5; color:#92400E;">This email is not registered on Gloy yet. First you need to create an account before you can log in.</span>
          <div style="margin-top:12px;">
            <a href="register.php?email=<?php echo urlencode($email); ?>" class="btn btn-primary btn-sm" style="padding:8px 18px; font-size:0.85rem; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
              <i class="bi bi-person-plus-fill"></i> Register Now
            </a>
          </div>
        </div>
      </div>
    <?php elseif ($error): ?>
      <div style="background-color:var(--accent-blush); color:var(--accent-terracotta); border:1.5px solid var(--accent-terracotta); padding:14px 18px; border-radius:var(--radius-md); font-size:0.9rem; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:1.2rem; flex-shrink:0;"></i>
        <span><?php echo e($error); ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" novalidate>
      <?php echo csrf_field(); ?>
      <div style="margin-bottom:20px;">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Email Address</label>
        <input type="email" name="email" class="input-field" value="<?php echo e($email); ?>" required placeholder="name@example.com">
      </div>

      <div style="margin-bottom:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
          <label style="font-size:0.85rem; font-weight:600; color:var(--text-heading);">Password</label>
          <a href="forgot_password.php" style="font-size:0.8rem; color:var(--primary-purple); font-weight:600;">Forgot password?</a>
        </div>
        <div class="password-toggle-wrap">
          <input type="password" name="password" id="login-password" class="input-field" required placeholder="••••••••">
          <button type="button" class="password-toggle-btn js-toggle-password" data-target="login-password" aria-label="Toggle password visibility">
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%; margin-bottom:20px; padding:12px; font-size:1rem; font-weight:700;">
        <i class="bi bi-box-arrow-in-right me-1"></i> Log In
      </button>
    </form>

    <div style="text-align:center; font-size:0.9rem; color:var(--text-muted); border-top:1px solid var(--border-color); padding-top:20px;">
      Don't have an account? <a href="register.php<?php echo !empty($email) ? '?email=' . urlencode($email) : ''; ?>" style="color:var(--primary-purple); font-weight:700;">Create One Here</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
