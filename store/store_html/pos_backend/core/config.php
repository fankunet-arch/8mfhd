<?php
/**
 * TopTea POS - Core Configuration
 * This file contains sensitive information and is stored outside the web root.
 * Engineer: Gemini | Date: 2025-10-26
 */

// --- Database Configuration (Same as all other systems) ---
$db_host = 'mhdlmskvtmwsnt5z.mysql.db';
$db_name = 'mhdlmskvtmwsnt5z';
$db_user = 'mhdlmskvtmwsnt5z';
$db_pass = 'p8PQF7M8ZKLVxtjvatMkrthFQQUB9';
$db_char = 'utf8mb4';

// --- Error Reporting ---
ini_set('display_errors', 1); // Set to 0 in production
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
    // In a real app, you would log this error, not display it.
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

// System timezone used by POS
if (!defined('APP_TZ')) {
    define('APP_TZ', 'Europe/Madrid');
}