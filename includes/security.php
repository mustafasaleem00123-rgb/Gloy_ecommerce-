<?php
/**
 * Gloy Skincare - Security bootstrap
 *
 * Har page/API is file ko sab se pehle load karta hai (auth_middleware.php, header.php,
 * api/*.php, admin/*.php sab). Yeh yahan karta hai:
 *   - Production mein display_errors band, errors sirf error_log mein
 *   - Session cookie: HttpOnly, SameSite=Lax, Secure (HTTPS par)
 *   - Admin ke liye 30 minute idle timeout
 *   - Har request par DB se check ke user abhi bhi active hai (disabled user foran logout)
 *   - Login par session fixation se bachao (gloy_login_user)
 *   - Login brute-force protection (5 galat tries => 15 minute lock)
 */

require_once __DIR__ . '/../config/env.php';

if (!defined('GLOY_ADMIN_IDLE_SECONDS')) define('GLOY_ADMIN_IDLE_SECONDS', 30 * 60);
if (!defined('GLOY_RL_WINDOW'))           define('GLOY_RL_WINDOW', 15 * 60);   // lock / window length
if (!defined('GLOY_RL_MAX_PER_ACCOUNT'))  define('GLOY_RL_MAX_PER_ACCOUNT', 5);
if (!defined('GLOY_RL_MAX_PER_IP'))       define('GLOY_RL_MAX_PER_IP', 20);    // alag alag emails try karne walon ke liye

/**
 * Aisi exception jiska message customer ko dikhana safe hai
 * (maslan "item out of stock"). Baqi saari exceptions generic message ke saath dikhti hain.
 */
if (!class_exists('GloyUserException')) {
    class GloyUserException extends Exception {}
}

function gloy_is_debug() {
    return strtolower((string)gloy_env('APP_DEBUG', 'false')) === 'true';
}

function gloy_is_https() {
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') return true;
    if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) return true;
    if (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') return true;
    return false;
}

function gloy_client_ip() {
    // Sirf REMOTE_ADDR: X-Forwarded-For client khud likh sakta hai, us par bharosa nahi.
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/** Server-side detail log karein, client ko sirf generic message dein. */
function gloy_log_error($context, $e) {
    error_log('[Gloy] ' . $context . ': ' . ($e instanceof Throwable ? $e->getMessage() : (string)$e));
}

/** JSON API ke catch block ke liye: user-facing exception ka message, baqi sab ka generic. */
function gloy_public_error_message(Throwable $e, $context, $generic = 'Something went wrong. Please try again.') {
    if ($e instanceof GloyUserException) {
        return $e->getMessage();
    }
    gloy_log_error($context, $e);
    return $generic;
}

function gloy_boot() {
    static $booted = false;
    if ($booted) return;
    $booted = true;

    $debug = gloy_is_debug();
    error_reporting(E_ALL);
    ini_set('display_errors', $debug ? '1' : '0');
    ini_set('display_startup_errors', $debug ? '1' : '0');
    ini_set('log_errors', '1');

    if (PHP_SAPI === 'cli') return;

    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => gloy_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    gloy_enforce_session();
}

function gloy_destroy_session() {
    $_SESSION = [];
    if (ini_get('session.use_cookies') && !headers_sent()) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    // Guest cart / CSRF ke liye nayi khali session
    if (!headers_sent()) {
        session_start();
        session_regenerate_id(true);
    }
}

/**
 * Admin idle timeout + "kya user abhi bhi active hai" check. Har request par chalta hai.
 */
function gloy_enforce_session() {
    if (empty($_SESSION['user_id'])) return;

    $now = time();
    $isAdmin = (($_SESSION['user_role'] ?? '') === 'admin');

    if ($isAdmin && isset($_SESSION['last_activity']) && ($now - (int)$_SESSION['last_activity']) > GLOY_ADMIN_IDLE_SECONDS) {
        gloy_destroy_session();
        return;
    }
    $_SESSION['last_activity'] = $now;

    try {
        require_once __DIR__ . '/../config/database.php';
        $pdo = getDBConnection();
        $st = $pdo->prepare('SELECT status, role FROM users WHERE id = ?');
        $st->execute([(int)$_SESSION['user_id']]);
        $row = $st->fetch();

        if (!$row || ($row['status'] ?? 'active') === 'disabled') {
            // User delete ho gaya ya admin ne disable kar diya: foran logout
            gloy_destroy_session();
            return;
        }
        // Role hamesha DB se (admin se admin-rights hataein to foran asar ho)
        $_SESSION['user_role'] = $row['role'];
    } catch (Throwable $e) {
        gloy_log_error('session user check failed', $e);
    }
}

/**
 * Successful login ke baad session set karein. Pehle session id badalta hai (session fixation).
 */
function gloy_login_user(array $user) {
    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
    $_SESSION['user_id']       = (int)$user['id'];
    $_SESSION['user_name']     = $user['name'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['user_role']     = $user['role'] ?? 'user';
    $_SESSION['last_activity'] = time();
}

/* ------------------------------------------------------------------------
 * Brute-force / rate limiting  (table: login_attempts, install script banata hai)
 * ---------------------------------------------------------------------- */

function gloy_rl_identifier($email) {
    return substr(strtolower(trim((string)$email)), 0, 190);
}

/**
 * Agar lock hai to baqi seconds lautata hai, warna 0.
 * Lock: kisi bhi account (ya IP) par 15 minute ke andar limit se zyada fail => aakhri fail ke 15 minute baad tak band.
 * Lock ke dauran nayi tries record nahi hotin, is liye lock barhta nahi.
 */
function gloy_rl_locked($scope, $email, $maxAccount = GLOY_RL_MAX_PER_ACCOUNT, $maxIp = GLOY_RL_MAX_PER_IP) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $pdo = getDBConnection();
        $now = time();
        $remaining = 0;

        $checks = [
            ['identifier', gloy_rl_identifier($email), $maxAccount],
            ['ip',         gloy_client_ip(),           $maxIp],
        ];
        foreach ($checks as [$col, $val, $limit]) {
            if ($val === '') continue;
            $latestStmt = $pdo->prepare("SELECT MAX(attempted_at) FROM login_attempts WHERE scope = ? AND $col = ? AND attempted_at > ?");
            $latestStmt->execute([$scope, $val, $now - GLOY_RL_WINDOW]);
            $latest = (int)$latestStmt->fetchColumn();
            if ($latest <= 0) continue;

            $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE scope = ? AND $col = ? AND attempted_at > ?");
            $cntStmt->execute([$scope, $val, $latest - GLOY_RL_WINDOW]);
            if ((int)$cntStmt->fetchColumn() >= $limit) {
                $remaining = max($remaining, $latest + GLOY_RL_WINDOW - $now);
            }
        }
        return max(0, $remaining);
    } catch (Throwable $e) {
        // Rate-limit table na ho to login band na ho; lekin log mein saaf nazar aaye
        gloy_log_error('rate limit check failed (install script chalayi?)', $e);
        return 0;
    }
}

function gloy_rl_fail($scope, $email) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $pdo = getDBConnection();
        $ins = $pdo->prepare('INSERT INTO login_attempts (scope, identifier, ip, attempted_at) VALUES (?, ?, ?, ?)');
        $ins->execute([$scope, gloy_rl_identifier($email), gloy_client_ip(), time()]);

        if (random_int(1, 50) === 1) {
            $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < ?')->execute([time() - 86400]);
        }
    } catch (Throwable $e) {
        gloy_log_error('rate limit record failed', $e);
    }
}

function gloy_rl_clear($scope, $email) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $pdo = getDBConnection();
        $pdo->prepare('DELETE FROM login_attempts WHERE scope = ? AND identifier = ?')
            ->execute([$scope, gloy_rl_identifier($email)]);
    } catch (Throwable $e) {
        gloy_log_error('rate limit clear failed', $e);
    }
}

function gloy_rl_message($seconds) {
    $mins = max(1, (int)ceil($seconds / 60));
    return 'Too many failed attempts. For your security, please try again in ' . $mins . ' minute' . ($mins === 1 ? '' : 's') . '.';
}

gloy_boot();
