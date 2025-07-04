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
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/qr_generator.php'; // QR Code functions

// 2.1 AUTLOAD VARUNA NAMESPACE CLASSES
spl_autoload_register(function ($class) {
    // Only handle classes in the Varuna namespace
    $prefix = 'Varuna\\';
    $base_dir = PROJECT_ROOT . '/src/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // not our namespace
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace namespace separators with directory separators, append .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
});


// 3. START THE SESSION
start_secure_session();


// 4. ESTABLISH THE GLOBAL DATABASE CONNECTION (ONLY ONCE)
$pdo = Database::getInstance()->getConnection();


// 5. GLOBAL CSRF TOKEN
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Migrate any existing signatures in the database that don't have file extensions
function migrateAuthoritySignatures() {
    global $pdo;
    
    // Check if the migration has been run
    $migrationKey = 'authority_signatures_migration_completed';
    if (isset($_SESSION[$migrationKey])) {
        return; // Already ran this session
    }
    
    try {
        $stmt = $pdo->query("SELECT user_id, signature_path FROM varuna_authority_signatures");
        $signatures = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($signatures as $signature) {
            $path = $signature['signature_path'];
            
            // Skip if path already has extension
            if (pathinfo($path, PATHINFO_EXTENSION)) {
                continue;
            }
            
            $basePath = __DIR__ . '/../public/uploads/authority/' . $path;
            
            // Check for files with various extensions
            foreach (['.png', '.jpg', '.jpeg', '.gif'] as $ext) {
                if (file_exists($basePath . $ext)) {
                    // Found a file with extension, update the database
                    $newPath = $path . $ext;
                    $updateStmt = $pdo->prepare("UPDATE varuna_authority_signatures SET signature_path = ? WHERE user_id = ?");
                    $updateStmt->execute([$newPath, $signature['user_id']]);
                    error_log("Updated signature path for user {$signature['user_id']}: $path -> $newPath");
                    break;
                }
            }
        }
        
        // Mark migration as completed for this session
        $_SESSION[$migrationKey] = true;
    } catch (Exception $e) {
        error_log("Error migrating authority signatures: " . $e->getMessage());
    }
}

// Run the migration
migrateAuthoritySignatures();