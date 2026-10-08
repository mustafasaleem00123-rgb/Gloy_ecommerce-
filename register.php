<?php
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/includes/cart_helper.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$name = '';
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name)) {
        $errors['name'] = 'Full Name is required.';
    } elseif (strlen($name) < 3 || strlen($name) > 60) {
        $errors['name'] = 'Full Name must be between 3 and 60 characters.';
    } elseif (preg_match('/[0-9]/', $name) || !preg_match("/^[a-zA-Z\s\.\'\-]+$/u", $name)) {
        $errors['name'] = 'Full Name cannot contain numbers. Alphabetic letters only.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
        $errors['email'] = 'Valid email address is required (e.g. name@domain.com).';
    } else {
        $emailParts = explode('@', $email);
        if (count($emailParts) !== 2 || !preg_match('/[a-zA-Z]/', $emailParts[0]) || !preg_match('/[a-zA-Z]/', $emailParts[1])) {
            $errors['email'] = 'Email username cannot be purely numbers. It must contain letters (e.g. ayesha@gmail.com).';
        }
    }
    
    if (!empty($phone)) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanPhone) > 11) {
            $errors['phone'] = 'Phone number cannot exceed 11 digits.';
        } else {
            $phone = $cleanPhone;
        }
    }

    // Password complexity: min 6 chars
    if (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirm) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // Duplicate email check
    if (empty($errors)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'An account with this email address already exists.';
        }
    }

    // Insert user
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $insStmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, 'user')");
        $insStmt->execute([$name, $email, $passwordHash, $phone]);

        $userId = $pdo->lastInsertId();

        // Merge guest cart if any, phir auto login (naya session id)
        merge_guest_cart_to_user($userId);
        gloy_login_user(['id' => $userId, 'name' => $name, 'email' => $email, 'role' => 'user']);

        header('Location: index.php');
        exit;
    }
}

$pageTitle = 'Create Account';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container section">
  <div style="max-width:500px; margin:0 auto; background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:40px; box-shadow:var(--shadow-md);">
    <div style="text-align:center; margin-bottom:32px;">
      <h1 style="font-size:2.2rem; margin-bottom:8px;">Join Gloy Skincare</h1>
      <p style="color:var(--text-muted); font-size:0.95rem;">Unlock exclusive offers &amp; track your routine.</p>
    </div>

    <form method="POST" action="register.php" id="register-form" novalidate>
      <?php echo csrf_field(); ?>
      <div style="margin-bottom:18px;">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">Full Name *</label>
        <input type="text" name="name" class="input-field" value="<?php echo e($name); ?>" oninput="this.value = this.value.replace(/[0-9]/g, '')" placeholder="e.g. Ayesha Khan" required>
        <?php if (isset($errors['name'])): ?><span style="color:var(--accent-terracotta); font-size:0.75rem;"><?php echo $errors['name']; ?></span><?php endif; ?>
      </div>

      <div style="margin-bottom:18px;">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">Email Address *</label>
        <input type="email" name="email" class="input-field" value="<?php echo e($email); ?>" placeholder="e.g. ayesha@gmail.com" required>
        <?php if (isset($errors['email'])): ?><span style="color:var(--accent-terracotta); font-size:0.75rem;"><?php echo $errors['email']; ?></span><?php endif; ?>
      </div>

      <div style="margin-bottom:18px;">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">Phone Number (Optional)</label>
        <input type="tel" name="phone" class="input-field" value="<?php echo e($phone); ?>" maxlength="11" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" placeholder="03168882788">
        <?php if (isset($errors['phone'])): ?><span style="color:var(--accent-terracotta); font-size:0.75rem;"><?php echo $errors['phone']; ?></span><?php endif; ?>
      </div>

      <div style="margin-bottom:18px;">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Password * (Min 6 characters)</label>
        <div class="password-toggle-wrap">
          <input type="password" name="password" id="register-password" class="input-field" required placeholder="••••••••">
          <button type="button" class="password-toggle-btn js-toggle-password" data-target="register-password" aria-label="Toggle password visibility">
            <i class="bi bi-eye"></i>
          </button>
        </div>
        <?php if (isset($errors['password'])): ?><span style="color:var(--accent-terracotta); font-size:0.75rem;"><?php echo $errors['password']; ?></span><?php endif; ?>
      </div>

      <div style="margin-bottom:24px;">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Confirm Password *</label>
        <div class="password-toggle-wrap">
          <input type="password" name="confirm_password" id="register-confirm-password" class="input-field" required placeholder="••••••••">
          <button type="button" class="password-toggle-btn js-toggle-password" data-target="register-confirm-password" aria-label="Toggle password visibility">
            <i class="bi bi-eye"></i>
          </button>
        </div>
        <?php if (isset($errors['confirm_password'])): ?><span style="color:var(--accent-terracotta); font-size:0.75rem;"><?php echo $errors['confirm_password']; ?></span><?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%; margin-bottom:20px;">Register Account</button>
    </form>

    <div style="text-align:center; font-size:0.9rem; color:var(--text-muted); border-top:1px solid var(--border-color); padding-top:20px;">
      Already have an account? <a href="login.php" style="color:var(--primary-purple); font-weight:600;">Log In Here</a>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const regForm = document.getElementById('register-form');
  if (regForm) {
    regForm.addEventListener('submit', (e) => {
      const nameInput = regForm.querySelector('input[name="name"]');
      const emailInput = regForm.querySelector('input[name="email"]');
      const passInput = regForm.querySelector('input[name="password"]');
      const confirmInput = regForm.querySelector('input[name="confirm_password"]');

      const name = nameInput ? nameInput.value.trim() : '';
      const email = emailInput ? emailInput.value.trim() : '';
      const pass = passInput ? passInput.value : '';
      const confirm = confirmInput ? confirmInput.value : '';

      if (!name || name.length < 3 || /[0-9]/.test(name) || !/^[a-zA-Z\s\.\'\-]+$/.test(name)) {
        e.preventDefault();
        window.showToast('Please enter a valid name with letters only (no numbers allowed).', 'error');
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

      if (!pass || pass.length < 6) {
        e.preventDefault();
        window.showToast('Password must be at least 6 characters long.', 'error');
        if (passInput) passInput.focus();
        return;
      }

      if (pass !== confirm) {
        e.preventDefault();
        window.showToast('Passwords do not match.', 'error');
        if (confirmInput) confirmInput.focus();
        return;
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
