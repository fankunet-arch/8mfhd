<?php
/**
 * Toptea Store - KDS
 * Core Configuration File
 * Engineer: Gemini | Date: 2025-10-23 | Revision: 2.2 (Path Constant Correction)
 */

// --- Database Configuration (Same as cpsys) ---
$db_host = 'mhdlmskvtmwsnt5z.mysql.db';
$db_name = 'mhdlmskvtmwsnt5z';
$db_user = 'mhdlmskvtmwsnt5z';
$db_pass = 'p8PQF7M8ZKLVxtjvatMkrthFQQUB9';
$db_char = 'utf8mb4';

// --- Application Settings ---
define('KDS_BASE_URL', 'http://store.toptea.es/kds/');

// --- Directory Paths (Relative to this new structure) ---
define('KDS_ROOT_PATH', dirname(__DIR__)); // Resolves to /web_toptea/store/store_html/kds

// --- CORE FIX: Define separate, correct paths for app (views) and helpers ---
define('KDS_APP_PATH', KDS_ROOT_PATH . '/app');
define('KDS_HELPERS_PATH', KDS_ROOT_PATH . '/helpers'); // Correct path to the helpers directory

// --- Error Reporting ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}