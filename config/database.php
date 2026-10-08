<?php
/**
 * Gloy Skincare - Database Connection (MySQL / phpMyAdmin & SQLite Fallback)
 *
 * Yeh file ab SIRF connection kholti hai. Tables banana, migrations aur seed data
 * config/schema.php mein hain jo scripts/install.php (ek baar, command line se) chalata hai.
 * Pehle yeh sab har page load par chalta tha, jis se site slow thi aur reviews mit jate the.
 */

require_once __DIR__ . '/env.php';

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // 1. MySQL (phpMyAdmin / XAMPP)
    $host = gloy_env('DB_HOST', 'localhost');
    $dbname = preg_replace('/[^a-zA-Z0-9_]/', '', gloy_env('DB_NAME', 'sc'));
    $username = gloy_env('DB_USER', 'root');
    $password = gloy_env('DB_PASS', '');

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $mysqlEx) {
        error_log('[Gloy] MySQL connection failed, falling back to SQLite: ' . $mysqlEx->getMessage());

        // 2. SQLite fallback agar MySQL active na ho
        $dbFile = __DIR__ . '/gloy_skincare.db';
        try {
            $pdo = new PDO('sqlite:' . $dbFile);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec("PRAGMA foreign_keys = ON;");
        } catch (PDOException $e) {
            error_log('[Gloy] Database connection failed: ' . $e->getMessage());
            http_response_code(500);
            die('Service temporarily unavailable. Please try again shortly.');
        }
    }

    return $pdo;
}
