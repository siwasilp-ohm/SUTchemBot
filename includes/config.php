<?php
/**
 * AI-Driven Chemical Inventory Management System
 * Configuration File
 */

// Start session before anything else (but only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load .env before defining constants
if (file_exists(__DIR__ . '/../.env')) {
    $envLines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'chem_inventory_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

define('APP_NAME', 'SUT chemBot');
define('APP_VERSION', '2.0.0');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/v1');
define('APP_TIMEZONE', 'Asia/Bangkok');

define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', APP_URL . '/assets/uploads/');
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB

define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'your-secret-key-here-change-in-production');
define('SESSION_LIFETIME', 86400 * 7); // 7 days

define('AI_API_KEY', $_ENV['AI_API_KEY'] ?? '');
define('AI_API_ENDPOINT', $_ENV['AI_API_ENDPOINT'] ?? 'https://api.openai.com/v1');
define('VISION_API_KEY', $_ENV['VISION_API_KEY'] ?? '');

define('EMAIL_SMTP_HOST', $_ENV['SMTP_HOST'] ?? '');
define('EMAIL_SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('EMAIL_SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('EMAIL_SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
define('EMAIL_FROM', $_ENV['EMAIL_FROM'] ?? 'noreply@cheminventory.local');

define('ENABLE_AR_FEATURES', true);
define('ENABLE_AI_FEATURES', true);
define('ENABLE_VISUAL_SEARCH', true);

define('DEFAULT_THEME', 'light');
define('DEFAULT_LANGUAGE', 'th');

date_default_timezone_set(APP_TIMEZONE);

// Error handling - disable display in production
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/error.log');

// ════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ════════════════════════════════════════════════════════════

/**
 * Get the base URL of the application
 * @return string
 */
function getBaseUrl() {
    return APP_URL;
}

/**
 * Redirect to a given URL
 * @param string $url
 * @param int $httpResponseCode
 * @return void
 */
function redirect($url, $httpResponseCode = 302) {
    header('Location: ' . $url, true, $httpResponseCode);
    exit();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    // Check if JWT token exists in session
    if (isset($_SESSION['user_token'])) {
        return true;
    }
    // Check if user object exists in session
    if (isset($_SESSION['user'])) {
        return true;
    }
    return false;
}
