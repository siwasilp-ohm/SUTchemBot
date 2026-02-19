<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('PRO_DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('PRO_DB_NAME', getenv('DB_NAME') ?: 'chem_inventory_db');
define('PRO_DB_USER', getenv('DB_USER') ?: 'root');
define('PRO_DB_PASS', getenv('DB_PASS') ?: '');
define('PRO_APP_NAME', 'SUT chemBot Pro Console');
define('PRO_APP_ENV', getenv('APP_ENV') ?: 'development');

if (PRO_APP_ENV === 'production') {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

function pro_csrf_token(): string {
    if (empty($_SESSION['pro_csrf_token'])) {
        $_SESSION['pro_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['pro_csrf_token'];
}

function pro_verify_csrf(?string $token): bool {
    if (!$token || empty($_SESSION['pro_csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['pro_csrf_token'], $token);
}

function pro_flash(?string $message = null, string $type = 'info'): ?array {
    if ($message !== null) {
        $_SESSION['pro_flash'] = ['message' => $message, 'type' => $type];
        return null;
    }

    if (!isset($_SESSION['pro_flash'])) {
        return null;
    }

    $flash = $_SESSION['pro_flash'];
    unset($_SESSION['pro_flash']);
    return $flash;
}
