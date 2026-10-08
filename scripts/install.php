<?php
/**
 * Gloy Skincare - One-time install / upgrade script
 *
 * Command line se chalayein (browser se nahi chalta):
 *     php scripts/install.php
 *
 * XAMPP (Windows):  C:\xampp\php\php.exe scripts\install.php
 *
 * Yeh karta hai:
 *   - Database (MySQL) banata hai agar nahi hai, tables + migrations chalata hai
 *   - SQLite fallback file mein bhi wohi (taake fallback par site na toote)
 *   - Khali tables mein seed data (kabhi DELETE nahi)
 *   - Purani payment screenshots ke hash nikaalta hai (duplicate detection ke liye)
 *   - Upload folders ki permission 0755 karta hai
 * Dobara chalana safe hai (idempotent).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden: run this script from the command line.\n");
}

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/schema.php';

function out($m) { echo $m . "\n"; }

$root = realpath(__DIR__ . '/..');
$targets = [];

/* ---- 1. MySQL ---- */
$host = gloy_env('DB_HOST', 'localhost');
$dbname = preg_replace('/[^a-zA-Z0-9_]/', '', gloy_env('DB_NAME', 'sc'));
try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", gloy_env('DB_USER', 'root'), gloy_env('DB_PASS', ''));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    $targets['MySQL (' . $dbname . ')'] = $pdo;
} catch (PDOException $e) {
    out('[skip] MySQL not reachable: ' . $e->getMessage());
}

/* ---- 2. SQLite fallback ---- */
try {
    $sqlite = new PDO('sqlite:' . $root . '/config/gloy_skincare.db');
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $sqlite->exec('PRAGMA foreign_keys = ON;');
    $targets['SQLite (config/gloy_skincare.db)'] = $sqlite;
} catch (PDOException $e) {
    out('[skip] SQLite not available: ' . $e->getMessage());
}

if (!$targets) {
    out('No database available. Check DB_* values in .env');
    exit(1);
}

foreach ($targets as $label => $db) {
    out("== $label ==");
    try {
        $seed = initDatabaseSchema($db);   // tables + migrations + seed (sirf khali tables mein)
        out('  schema + migrations: OK');
        if (!empty($seed['admin_password'])) {
            out('  NEW ADMIN PASSWORD (ADMIN_SEED_PASSWORD .env mein nahi tha, ab save kar lein): ' . $seed['admin_password']);
        }
        $n = gloy_backfill_screenshot_hashes($db);
        out("  screenshot hashes backfilled: $n");
        foreach (gloy_ensure_fraud_indexes($db) as $col => $state) {
            out("  index on $col: $state");
        }

        // Index report
        foreach (['uq_orders_transaction_id' => 'transaction_id', 'uq_orders_screenshot_hash' => 'payment_screenshot_hash'] as $idx => $col) {
            $dups = $db->query("SELECT COUNT(*) FROM (SELECT $col FROM orders WHERE $col IS NOT NULL AND $col != '' GROUP BY $col HAVING COUNT(*) > 1) t")->fetchColumn();
            if ((int)$dups > 0) {
                out("  NOTE: orders.$col mein $dups duplicate value(s) hain (purane orders). Admin > Orders mein 'Duplicate' badge dikhega.");
            }
        }
    } catch (Throwable $e) {
        out('  FAILED: ' . $e->getMessage());
    }
}

/* ---- 3. Folder permissions & dirs ---- */
foreach (['assets/uploads', 'assets/uploads/payments', 'assets/images/reviews'] as $rel) {
    $dir = $root . '/' . $rel;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    @chmod($dir, 0755);
    out("dir ok (0755): $rel");
}

out("\nDone. Is script ko dobara chalana safe hai. Ab 'scripts/' folder production server par rakhna zaroori nahi.");
