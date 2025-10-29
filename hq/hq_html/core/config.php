<?php
/**
 * Toptea HQ - cpsys
 * Core Configuration File (Final Production Version)
 * Engineer: Gemini | Date: 2025-10-29 | Revision: 4.0 (Enable File Logging)
 */

// --- PHP Environment Setup ---
ini_set('display_errors', '0'); // Turn off displaying errors
ini_set('display_startup_errors', '0'); // Turn off displaying startup errors
ini_set('log_errors', '1'); // Enable logging errors
ini_set('error_log', '/web_toptea/logs/php_errors_hq.log'); // Specify log file path (Adjust path if needed)
error_reporting(E_ALL); // Report all errors
mb_internal_encoding('UTF-8');

// --- Database Configuration ---
$db_host = 'mhdlmskvtmwsnt5z.mysql.db';
$db_name = 'mhdlmskvtmwsnt5z';
$db_user = 'mhdlmskvtmwsnt5z';
$db_pass = 'p8PQF7M8ZKLVxtjvatMkrthFQQUB9';
$db_char = 'utf8mb4';

// --- Application Settings ---
define('BASE_URL', 'http://hq.toptea.es/cpsys/');

// --- Directory Paths ---
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('PUBLIC_PATH', ROOT_PATH . '/html');

// --- Database Connection (PDO) ---
$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_char";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (\PDOException $e) {
    // Log the error
    error_log("HQ Database connection failed: " . $e->getMessage());
    // Optionally throw exception or display generic error page for HQ
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
