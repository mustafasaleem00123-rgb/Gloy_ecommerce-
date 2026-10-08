<?php
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/includes/mail_helper.php';

$message = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $email = trim($_POST['email'] ?? '');
    $wait = gloy_rl_locked('forgot', $email, 5, 10);
    if ($wait > 0) {
        $message = gloy_rl_message($wait);
    } else {
        // Har request gini jati hai (email registered ho ya nahi), taake koi inbox ko spam ya emails ko probe na kare
        gloy_rl_fail('forgot', $email);

        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                $pdo = getDBConnection();
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Database mein token ka hash save hota hai, asli token nahi
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
                $stmt->execute([hash('sha256', $token), $expiry, $email]);

                if ($stmt->rowCount() > 0) {
                    $scheme = gloy_is_https() ? 'https' : 'http';
                    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                    $resetUrl = "$scheme://$host$scriptDir/reset_password.php?token=$token";

                    // Development mode / fallback logging
                    error_log('[Gloy] Password reset link for ' . $email . ': ' . $resetUrl);

                    // Send email via Brevo / Resend HTTP REST API (Works on InfinityFree & Localhost)
                    $subject = "Reset Your Gloy Skincare Password";
                    $htmlBody = "
                        <div style='font-family: Arial, sans-serif; max-width: 550px; margin: 0 auto; padding: 25px; border: 1px solid #E5E7EB; border-radius: 12px; background-color: #ffffff;'>
                            <h2 style='color: #4A3B32; text-align: center;'>Gloy Skincare Password Reset</h2>
                            <p style='color: #4B5563; font-size: 15px;'>Hello,</p>
                            <p style='color: #4B5563; font-size: 15px;'>We received a request to reset your Gloy Skincare account password. Click the button below to set a new password:</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='$resetUrl' style='background-color: #4A3B32; color: #ffffff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold; display: inline-block;'>Reset Password Now</a>
                            </div>
                            <p style='color: #6B7280; font-size: 13px;'>If the button doesn't work, copy and paste this link into your browser:</p>
                            <p style='color: #4A3B32; font-size: 13px; word-break: break-all;'><a href='$resetUrl'>$resetUrl</a></p>
                            <p style='color: #9CA3AF; font-size: 12px; margin-top: 30px; border-top: 1px solid #F3F4F6; padding-top: 15px; text-align: center;'>This link is valid for 1 hour. If you did not request this, please ignore this email.</p>
                        </div>
                    ";
                    $plainText = "Reset your password by visiting: $resetUrl";

                    gloy_send_email($email, $subject, $htmlBody, $plainText);
                }
            } catch (Exception $ex) {
                gloy_log_error('forgot_password error', $ex);
            }
        }
        // Hamesha ek hi message, chahe email registered ho ya nahi
        $message = 'If an account exists for that email, a password reset link has been sent. Please check your email inbox.';
    }
}

$pageTitle = 'Forgot Password';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container section">
  <div style="max-width:450px; margin:0 auto; background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:40px; box-shadow:var(--shadow-md);">
    <div style="text-align:center; margin-bottom:28px;">
      <h1 style="font-size:2rem; margin-bottom:8px;">Reset Password</h1>
      <p style="color:var(--text-muted); font-size:0.9rem;">Enter your account email to receive a password reset link.</p>
    </div>

    <?php if ($message): ?>
      <div style="background-color:var(--bg-accent-sage); color:var(--primary-sage); padding:14px; border-radius:var(--radius-md); font-size:0.9rem; margin-bottom:20px;">
        <?php echo e($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="forgot_password.php">
      <?php echo csrf_field(); ?>
      <div style="margin-bottom:20px;">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">Account Email Address</label>
        <input type="email" name="email" class="input-field" value="<?php echo e($email); ?>" required placeholder="name@example.com">
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%; margin-bottom:20px;">Send Reset Link</button>
    </form>

    <div style="text-align:center; font-size:0.85rem;">
      <a href="login.php" style="color:var(--text-muted);">← Back to Login</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
