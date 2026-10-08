<?php
/**
 * Gloy Skincare - Minimal .env loader (no Composer needed)
 * Usage: gloy_env('DB_PASS', 'default')
 */
if (!function_exists('gloy_env')) {
    function gloy_env($key, $default = null) {
        static $vars = null;
        if ($vars === null) {
            $vars = [];
            $file = __DIR__ . '/../.env';
            if (is_readable($file)) {
                foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    $line = trim($line);
                    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                        continue;
                    }
                    list($k, $v) = explode('=', $line, 2);
                    $k = trim($k);
                    $v = trim($v);
                    if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
                        $v = substr($v, 1, -1);
                    }
                    $vars[$k] = $v;
                }
            }
        }
        if (isset($vars[$key])) return $vars[$key];
        $sys = getenv($key);
        return ($sys !== false) ? $sys : $default;
    }
}
