<?php
/**
 * VARUNA System - Global Bootstrap & Initialization File
 */

define('PROJECT_ROOT', dirname(__DIR__));

// 1. GLOBAL ERROR & TIMEZONE CONFIGURATION
date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
// --- FIX: Change '0' to 0 for consistency ---
ini_set('display_errors', 0); // Do not show errors to users
ini_set('log_errors', 1);
ini_set('error_log', PROJECT_ROOT . '/logs/error.log'); // Use the constant for a reliable path


// 2. LOAD CONFIGURATION & LIBRARIES
require_once dirname(__DIR__) . '/config.php'; // Gets DB credentials and BASE_URL
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/functions.php';
require_once __DIR__ . '/core/Database.php'; // <-- Include the new Database class


// 3. START THE SESSION
start_secure_session();


// 4. ESTABLISH THE GLOBAL DATABASE CONNECTION (ONLY ONCE)
$pdo = Database::getInstance()->getConnection();


// 5. GLOBAL CSRF TOKEN
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}