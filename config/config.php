<?php
/**
 * ShopEase - Configuration & Environment Setup
 * Central configuration file for database, session, security, and paths.
 */

// 1. Prevent direct execution in browser if accessed out of context
defined('SHOPEASE_INIT') || define('SHOPEASE_INIT', true);

// 2. Error Reporting (Development vs Production)
define('APP_ENV', 'development'); // Change to 'production' on live deployment

if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../error.log');
    error_reporting(0);
}

// 3. Database Credentials (Default XAMPP configuration)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'shopease');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// 4. Application Identity
define('APP_NAME', 'ShopEase');
define('APP_VERSION', '3.0.0');
define('APP_TAGLINE', '3D Shop Inventory & Sales Management');

// 5. Base Path & URL Auto-Detection
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_PATH . '/assets/uploads');

// Auto-detect web accessible base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
// Normalize baseUrl
$baseUrl = rtrim($protocol . $host . $scriptDir, '/');
if (substr($baseUrl, -6) === '/pages' || substr($baseUrl, -4) === '/api') {
    $baseUrl = dirname($baseUrl);
}
define('BASE_URL', $baseUrl);

// 6. Hardened Session Settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    
    $cookieParams = [
        'lifetime' => 86400, // 24 hours
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    session_set_cookie_params($cookieParams);
    session_start();
}

// 7. Auto-load Classes from /classes directory
spl_autoload_register(function ($class) {
    $file = ROOT_PATH . '/classes/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 8. Global Helper Functions

/**
 * Escape HTML output for XSS protection
 */
function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Generate a relative application URL
 */
function url(string $path = ''): string {
    $cleanPath = ltrim($path, '/');
    return BASE_URL . ($cleanPath ? '/' . $cleanPath : '');
}

/**
 * Generate asset URL
 */
function asset(string $path = ''): string {
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Safe redirect helper
 */
function redirect(string $path): void {
    header('Location: ' . url($path));
    exit;
}

/**
 * Standard JSON Response helper
 */
function jsonResponse(bool $success, string $message = '', $data = null, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
